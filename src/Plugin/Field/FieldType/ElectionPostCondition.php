<?php

namespace Drupal\election\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Entity\EntityFieldManagerInterface;

/**
 * Provides a field type of election post condition.
 * 
 * @FieldType(
 *   id = "election_post_condition",
 *   label = @Translation("Election post condition field"),
*    description = @Translation("Stores information about a conditon for nominating or voting for an election post."),
 *   default_formatter = "election_post_condition_formatter",
 *   default_widget = "election_post_condition_widget"
 * )
 */
class ElectionPostCondition extends FieldItemBase {

    /**
     * {@inheritdoc}
     */
    public static function schema(FieldStorageDefinitionInterface $field_definition) {
        return array(
            'columns' => array(
                'condition_type' => array(
                    'type' => 'text',
                    'size' => 'small',
                    'not null' => true,
                ),
                'condition_operator' => array(
                    'type' => 'text',
                    'size' => 'small',
                    'not null' => true,
                ),
                'user_role' => array(
                    'type' => 'int',
                    'size' => 'normal',
                    'not null' => FALSE,
                ),
                'group' => array(
                    'type' => 'int',
                    'size' => 'normal',
                    'not null' => FALSE,
                ),
                'group_role' => array(
                    'type' => 'int',
                    'size' => 'normal',
                    'not null' => FALSE,
                ),
                'user_field_name' => array(
                    'type' => 'text',
                    'size' => 'small',
                    'not null' => true,
                ),
                'user_field_operator' => array(
                    'type' => 'text',
                    'size' => 'small',
                    'not null' => true,
                ),
                'user_field_value' => array(
                    'type' => 'text',
                    'size' => 'normal',
                    'not null' => true,
                ),
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
        $properties = [];
        $properties['condition_type'] = DataDefinition::create('string');
        // Could be: role, group, user_field_name 
        // These are also the names of the field required to have value for the condition to work - see isEmpty

        // Allow for grouped conditions for a post
        $properties['condition_operator'] = DataDefinition::create('string');

        // Condition type: role
        $properties['user_role'] = DataDefinition::create('integer')
            ->setLabel(t('Role ID reference'))
            ->setDescription(t('The ID of the referenced role.'))
            ->setSetting('unsigned', TRUE);

        // Condition type: group
        $properties['group'] = DataDefinition::create('integer')
            ->setLabel(t('Role ID reference'))
            ->setDescription(t('The ID of the referenced role.'))
            ->setSetting('unsigned', TRUE);
        $properties['group_role'] = DataDefinition::create('integer')
            ->setLabel(t('Role ID reference'))
            ->setDescription(t('The ID of the referenced role.'))
            ->setSetting('unsigned', TRUE);

        // Condition type: user_field_value
        $properties['user_field_name'] = DataDefinition::create('string');
        $properties['user_field_operator'] = DataDefinition::create('string');
        $properties['user_field_value'] = DataDefinition::create('string');
    
        return $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty() {
        $condition_type = $this->get('condition_type')->getValue();
        if($condition_type === NULL || $condition_type === '') {
            return true;
        }
        if($this->get($condition_type) && ($this->get($condition_type) == NULL || $this->get($condition_type) === '')) {
            return true;
        }
    }
}