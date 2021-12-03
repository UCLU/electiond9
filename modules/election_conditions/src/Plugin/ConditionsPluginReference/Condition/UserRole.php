<?php

namespace Drupal\election_conditions\Plugin\ConditionsPluginReference\Condition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\election_conditions\Plugin\ElectionConditionBase;

/**
 * Condition.
 *
 * @ConditionsPluginReference(
 *   id = "election_user_role",
 *   condition_types = {
 *     "election",
 *   },
 *   label = @Translation("User role(s)"),
 *   display_label = @Translation("User has specific user role(s)"),
 *   category = @Translation("Users"),
 *   weight = 0,
 * )
 */
class UserRole extends ElectionConditionBase {

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

    $roles = NULL;
    $role_ids = $this->configuration['user_roles'];
    if (!empty($role_ids)) {
      $roles = $this->productStorage->loadMultiple($role_ids);
    }
    $form['user_roles'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t('Roles'),
      '#default_value' => $roles,
      '#required' => TRUE,
      '#maxlength' => NULL,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);
    $role_ids = array_column($values['user_roles'], 'target_id');
    $this->configuration['user_roles'] = $role_ids;
  }
}
