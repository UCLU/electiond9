<?php

declare(strict_types=1);

namespace Drupal\complex_conditions;

use Drupal\complex_conditions\Plugin\ComplexConditions\Condition\AndOperator;
use Drupal\complex_conditions\Plugin\ComplexConditions\Condition\OrOperator;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

//https://git.drupalcode.org/project/commerce_conditions_plus/-/blob/1.0.x/src/ConditionsEvaluator.php
final class ConditionsEvaluator {

  /**
   * @var Drupal\Core\Entity\EntityInterface
   */
  public $entity;

  /**
   * @var Drupal\Core\Session\AccountInterface
   */
  public $account;

  /**
   * @var array
   */
  public $parameters;

  /**
   * Create new evaluator.
   *
   * @param Drupal\Core\Entity\EntityInterface $entity
   * @param Drupal\Core\Session\AccountInterface $account
   * @param array $parameters
   */
  public function __construct(EntityInterface $entity, AccountInterface $account, array $parameters) {
    $this->entity = $entity;
    $this->account = $account;
    $this->parameters = $parameters;
  }

  public function setEntity(EntityInterface $entity) {
    $this->entity = $entity;
  }

  public function setAccount(AccountInterface $account) {
    $this->account = $account;
  }

  public function setParameters(array $parameters) {
    $this->parameters = $parameters;
  }

  /**
   * @param \Drupal\complex_conditions\Plugin\ComplexConditions\Condition\ConditionInterface[] $conditions
   * @param array<string, EntityInterface> $targets
   *
   * @return bool
   */
  public function execute(array $conditions, string $base_operator): bool {
    assert($base_operator === 'AND' || $base_operator === 'OR');

    $organized_conditions = $this->organizeConditions($conditions, $base_operator);

    $boolean = !($base_operator === 'AND');
    foreach ($organized_conditions as $condition_group) {
      $result = $this->evaluateConditionGroup($condition_group['conditions'], $condition_group['operator']);
      if ($result === $boolean) {
        return $boolean;
      }
    }
    return !$boolean;
  }

  /**
   * @param \Drupal\complex_conditions\Plugin\ComplexConditions\Condition\ConditionInterface[] $conditions
   * @param array<string, EntityInterface> $targets
   *
   * @return bool
   */
  public function executeRequirements(array $conditions, string $base_operator): array {
    assert($base_operator === 'AND' || $base_operator === 'OR');

    $organized_conditions = $this->organizeConditions($conditions, $base_operator);

    $requirements = [];
    foreach ($organized_conditions as $condition_group_key => $condition_group) {
      $requirementsForGroup = $this->evaluateConditionGroupRequirements($condition_group['conditions'], $condition_group['operator']);
      foreach ($requirementsForGroup as $requirementForGroup) {
        $requirementForGroup->setGroup($condition_group['group_name']);
        $requirements[] = $requirementForGroup;
      }
    }

    $requirements = ConditionRequirement::sortByGroup($requirements);

    return $requirements;
  }

  private function organizeConditions(array $conditions, string $base_operator) {
    $organized_conditions = [
      'ungrouped' => [
        'operator' => $base_operator,
        'conditions' => [],
        'group_name' => '',
      ],
    ];
    foreach ($conditions as $condition) {
      $configuration = $condition->getConfiguration();
      $configuration['depth'] = (!isset($configuration['depth'])) ? 0 : (int) $configuration['depth'];
      if ($condition instanceof AndOperator || $condition instanceof OrOperator) {
        $parent_key = $condition->getPluginId() . ':' . $configuration['depth'];
        $group_name = $condition->getDisplayLabel() . ' [group ' . ($configuration['depth'] + 1) . ']';
        $organized_conditions[$parent_key] = [
          'operator' => $condition instanceof OrOperator ? 'OR' : 'AND',
          'conditions' => [],
          'group_name' => $group_name,
        ];
      } elseif (!empty($configuration['parent'])) {
        $parent_key = $configuration['parent'] . ':' . ($configuration['depth'] - 1);
        $organized_conditions[$parent_key]['conditions'][] = $condition;
      } else {
        $organized_conditions['ungrouped']['conditions'][] = $condition;
      }
    }
    return $organized_conditions;
  }

  /**
   * @param array $conditions
   * @param string $operator
   *
   * @return bool
   */
  private function evaluateConditionGroup(array $conditions, string $operator): bool {
    $requirements = $this->evaluateConditionGroupRequirements($conditions, $operator);

    if ($operator === 'OR') {
      $hasSucceedingRequirement = ConditionRequirement::anyPassed($requirements);
      return $hasSucceedingRequirement;
    } else {
      $hasFailingRequirement = ConditionRequirement::allPassed($requirements);
      return $hasFailingRequirement;
    }
  }

  /**
   * @param array $conditions
   * @param string $operator
   *
   * @return array
   */
  private function evaluateConditionGroupRequirements(array $conditions, string $operator): array {
    assert($operator === 'AND' || $operator === 'OR');

    $requirements = [];

    foreach ($conditions as $condition) {
      $negated = $condition->isNegated();

      $requirementsEvaluated = $condition->evaluateRequirements($this->entity, $this->account, $this->parameters);

      // Negate if relevant
      if ($negated) {
        foreach ($requirementsEvaluated as $id => $requirement) {
          $requirement->negate();
          $requirementsEvaluated[$id] = $requirement;
        }
      }

      $requirements = array_merge($requirements, $requirementsEvaluated);
    }

    return $requirements;
  }
}
