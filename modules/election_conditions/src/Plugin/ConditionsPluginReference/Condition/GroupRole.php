<?php

namespace Drupal\election_conditions\Plugin\ConditionsPluginReference\Condition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\election_conditions\Plugin\ElectionConditionBase;

/**
 * Condition.
 *
 * @ConditionsPluginReference(
 *   id = "election_group_role",
 *   condition_types = {
 *     "election",
 *   },
 *   label = @Translation("User has role in group"),
 *   display_label = @Translation("User has role in group "),
 *   category = @Translation("Group memberships"),
 *   weight = 0,
 * )
 */
class GroupRole extends ElectionConditionBase {
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'groups' => [],
      'groups_any_or_all' => [],
      'group_roles' => [],
      'group_roles_any_or_all' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $groupOptions = [];
    $form['groups'] = [
      '#type' => 'select',
      '#title' => $this->t('Groups'),
      '#options' => $groupOptions,
      '#default_value' => $this->configuration['groups'],
    ];
    $form['groups_any_or_all'] = [
      '#type' => 'select',
      '#title' => $this->t('In any or all groups'),
      '#options' => [
        'any' => 'Any',
        'all' => 'All',
      ],
      '#default_value' => $this->configuration['groups_any_or_all'],
    ];

    $roles = [];
    $roleOptions = [];
    foreach ($roles as $role) {
      $roleOptions[$role->id()] = $role->label();
    }
    $form['group_roles'] = [
      '#type' => 'select',
      '#title' => $this->t('Group roles'),
      '#options' => $roleOptions,
      '#default_value' => $this->configuration['group_roles'],
    ];

    $form['group_roles_any_or_all'] = [
      '#type' => 'select',
      '#title' => $this->t('Has any or all roles'),
      '#options' => [
        'any' => 'Any',
        'all' => 'All',
      ],
      '#default_value' => $this->configuration['group_roles_any_or_all'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);

    $this->configuration['groups'] = $values['groups'];
    $this->configuration['groups_any_or_all'] = $values['groups_any_or_all'];
    $this->configuration['group_roles'] = $values['group_roles'];
    $this->configuration['group_roles_any_or_all'] = $values['group_roles_any_or_all'];
  }
}
