<?php

namespace Drupal\election_conditions\Plugin\ComplexConditions\Condition;

use Drupal\complex_conditions\ConditionRequirement;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\election_conditions\Plugin\ElectionConditionBase;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupRole;
use Drupal\user\UserInterface;

/**
 * Condition.
 *
 * @ComplexConditions(
 *   id = "election_group_role",
 *   condition_types = {
 *     "election",
 *   },
 *   label = @Translation("User has role(s) in group(s)"),
 *   display_label = @Translation("User has role(s) in group(s)"),
 *   category = @Translation("Group memberships"),
 *   weight = 0,
 * )
 */
class ElectionGroupRole extends ElectionConditionBase {
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
      '#title' => $this->t('Groups'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'group',
      '#tags' => TRUE,
      '#default_value' => $this->configuration['groups'],
      '#attributes' => [
        'class' => ['container-inline'],
      ],
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

    $form['group_roles'] = [
      '#title' => $this->t('Group roles'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'group_role',
      '#tags' => TRUE,
      '#default_value' => $this->configuration['group_roles'],
      '#attributes' => [
        'class' => ['container-inline'],
      ],
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

  /**
   * Return true or false if access.
   *
   * @param string $phase
   *
   * @return boolean
   */
  public function evaluate(EntityInterface $entity, AccountInterface $account, $parameters = []) {
    $this->assertParameters($parameters);
    $requirements = [];

    $rolesToCheck = $this->configuration['group_roles'];
    $rolesNames = [];
    foreach ($rolesToCheck as $roleId) {
      $rolesNames[] = GroupRole::load($roleId)->label();
    }

    $groups = Group::loadMultiple(array_column($this->configuration['groups'], 'target_id'));

    $groupMatches = [];

    foreach ($groups as $group) {
      $userRoles = $this->getGroupRoles($account, $group);
      $groupMatches[$group->id()]['any'] = count(array_intersect($rolesToCheck, $userRoles)) > 0;
      $groupMatches[$group->id()]['all'] = count(array_intersect($rolesToCheck, $userRoles)) == count($rolesToCheck);
    }

    // $requirements['has_any_group_roles'] = new ConditionRequirement([
    //   'id' => 'has_any_group_roles',
    //   'label' => t('User has any of the following group roles: @roles', [
    //     '@roles' => implode(', ', $rolesNames),
    //   ]),
    //   'pass' => ,
    // ]);

    $this->dispatchRequirementEvents($requirements);
    return $requirements;
  }

  public function getGroupRoles(UserInterface $account, Group $group) {
    if ($member = $group->getMember($account)) {
      $membership = $member->getGroupContent();
      return $membership->group_roles;
    }
    return NULL;
  }
}
