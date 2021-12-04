<?php

namespace Drupal\conditions_plugin_reference\Plugin\ConditionsPluginReference\Condition;

use Drupal\conditions_plugin_reference\Annotation\ConditionsPluginReference;
use Drupal\conditions_plugin_reference\Plugin\ConditionsPluginReference\Condition\ConditionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\Role;

/**
 * Condition.
 *
 * @ConditionsPluginReference(
 *   id = "election_user_role",
 *   label = @Translation("User role(s)"),
 *   display_label = @Translation("User has specific user role(s)"),
 *   category = @Translation("Users"),
 *   weight = 0,
 * )
 */
class UserRole extends ConditionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'user_roles' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $roles = Role::loadMultiple();
    $roleOptions = [];
    foreach ($roles as $role) {
      $roleOptions[$role->id()] = $role->label();
    }
    $form['user_roles'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t('Roles'),
      '#options' => $roleOptions,
      '#default_value' => $this->configuration['user_roles'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);
    // $role_ids = array_column($values['user_roles'], 'target_id');
    $this->configuration['user_roles'] = $values['user_roles'];
  }
}
