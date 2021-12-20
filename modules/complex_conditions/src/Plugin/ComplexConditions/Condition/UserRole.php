<?php

namespace Drupal\complex_conditions\Plugin\ComplexConditions\Condition;

use Drupal\complex_conditions\Annotation\ComplexConditions;
use Drupal\complex_conditions\ConditionRequirement;
use Drupal\complex_conditions\Event\ConditionRequirementEvents;
use Drupal\complex_conditions\Plugin\ComplexConditions\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\Role;

/**
 * Condition.
 *
 * @ComplexConditions(
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
      'user_roles_any_all' => '',
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
    $form['user_roles_any_all'] = [
      '#type' => 'select',
      '#title' => $this->t('Require any or all of these roles'),
      '#options' => [
        'any' => 'Any of these role(s)',
        'all' => 'All of these role(s)',
      ],
      '#default_value' => $this->configuration['user_roles_any_all'],
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
    $this->configuration['user_roles_any_all'] = $values['user_roles_any_all'];
  }

  /**
   * {@inheritdoc}
   */
  public function evaluateRequirements(EntityInterface $entity, AccountInterface $account, $parameters = []) {
    $requirements = parent::evaluateRequirements($entity, $account, $parameters);

    $rolesToCheck = $this->configuration['user_roles'];
    $rolesNames = [];
    foreach ($rolesToCheck as $roleId) {
      $rolesNames[] = Role::load($roleId)->label();
    }
    $userRoles = $account->getRoles();

    if ($this->configuration['user_roles_any_all'] == 'any') {
      // Any:
      $requirements[] = new ConditionRequirement([
        'id' => 'has_any_roles',
        'label' => t('User has any of the following roles: @roles', [
          '@roles' => implode(', ', $rolesNames),
        ]),
        'description' => t('Roles are generally granted by the site administrator.'),
        'pass' => count(array_intersect($rolesToCheck, $userRoles)) > 0,
      ]);
    } else {
      // All:
      $requirements[] = new ConditionRequirement([
        'id' => 'has_all_roles',
        'label' => t('User has all the following roles: @roles', [
          '@roles' => implode(', ', $rolesNames),
        ]),
        'description' => t('Roles are generally granted by the site administrator.'),
        'pass' => count(array_intersect($rolesToCheck, $userRoles)) == count($rolesToCheck),
      ]);
    }

    $this->dispatchRequirementEvents($requirements);

    return $requirements;
  }
}
