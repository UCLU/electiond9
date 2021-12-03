<?php

namespace Drupal\election_conditions;

use Drupal\conditions_plugin_reference\ConditionsEntityTrait;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\election\Entity\Election;

trait ElectionConditionsTrait {

  use ConditionsEntityTrait;

  public static function addElectionConditionsFields(&$fields, string $entity_type) {
    /**
     * You can have conditions for each of the election phases.
     * You can also choose to share conditions between phases.
     * You can also inherit election conditions for election posts - or not.
     */

    if ($entity_type == 'election') {
      $typeNotice = t('The election conditions can be shared or overridden with any individual posts within the election.');
    } else {
      $typeNotice = t('The post will also automatically inherit any conditions set for the election, unless you disable that using the "Inherit election conditions" option.');

      $fields['conditions_inherit_election'] = BaseFieldDefinition::create('list_string')
        ->setLabel(t('Inherit conditions from election'))
        ->setSettings([
          'allowed_values' => [
            'inherit' => 'Inherit election conditions - both election and post conditions will apply',
            'ignore' => 'Ignore election conditions - only post conditions will apply',
          ],
        ])
        ->setDisplayOptions('form', [
          'type' => 'options_select',
        ])
        ->setRequired(TRUE)
        ->setDefaultValue('inherit')
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayConfigurable('view', TRUE);
    }

    $condition_categories = Election::ELECTION_PHASES;
    foreach ($condition_categories as $key) {
      $name = Election::getPhaseName($key);

      $defaultValue = 'none';
      $allowedValues = [
        'none' => $entity_type == 'election_post' ? 'No post-specific conditions for ' . $name : 'No conditions for ' . $name,
        $key => 'Unique set of ' . ($entity_type == 'election_post' ? 'post ' : '') . ' conditions for ' . $name,
      ];
      foreach (Election::ELECTION_PHASES as $phaseKey) {
        $defaultValue = !$defaultValue ? $phaseKey : $defaultValue;
        if ($key != $phaseKey) {
          $allowedValues[$phaseKey] = 'Same as ' . Election::getPhaseName($phaseKey);
        }
      };

      // Conditions will be collected shared automatically across whatever type is selected.
      $fields['conditions_' . $key . '_same_as'] = BaseFieldDefinition::create('list_string')
        ->setLabel((t('@type conditions', ['@type' => $name])))
        ->setDescription($typeNotice)
        ->setSettings([
          'allowed_values' => $allowedValues,
        ])
        ->setDisplayOptions('form', [
          'type' => 'options_select',
        ])
        ->setRequired(TRUE)
        ->setDefaultValue($defaultValue)
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayConfigurable('view', TRUE);

      $fields['conditions_' . $key] = BaseFieldDefinition::create('conditions_plugin_item:conditions_plugin_reference')
        ->setLabel(t('@name conditions', [
          '@name' => $name,
        ]))
        ->setDescription($typeNotice)
        ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
        ->setDisplayOptions('form', [
          'type' => 'conditions_plugin_reference_conditions_table_election',
        ])
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayConfigurable('view', TRUE);
    }
  }
}
