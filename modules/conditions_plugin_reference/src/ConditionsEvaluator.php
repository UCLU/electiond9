<?php

declare(strict_types=1);

namespace Drupal\conditions_plugin_reference;

use Drupal\conditions_plugin_reference\Plugin\ConditionPluginReference\Condition\AndOperator;
use Drupal\conditions_plugin_reference\Plugin\ConditionsPluginReference\Condition\OrOperator;
use Drupal\Core\Entity\EntityInterface;

//https://git.drupalcode.org/project/commerce_conditions_plus/-/blob/1.0.x/src/ConditionsEvaluator.php
final class ConditionsEvaluator {


  /**
   * @param \Drupal\conditions_plugin_reference\Plugin\ConditionsPluginReference\Condition\ConditionInterface[] $conditions
   * @param array<string, EntityInterface> $targets
   *
   * @return bool
   */
  public function execute(array $conditions, string $base_operator, array $targets): bool {
    assert($base_operator === 'AND' || $base_operator === 'OR');
    $organized_conditions = [
      'ungrouped' => [
        'operator' => $base_operator,
        'conditions' => [],
      ],
    ];
    foreach ($conditions as $condition) {
      $configuration = $condition->getConfiguration();
      $configuration['depth'] = (!isset($configuration['depth'])) ? 0 : (int) $configuration['depth'];
      if ($condition instanceof AndOperator) {
        $parent_key = $condition->getPluginId() . ':' . $configuration['depth'];
        $organized_conditions[$parent_key] = [
          'operator' => 'AND',
          'conditions' => [],
        ];
      } elseif ($condition instanceof OrOperator) {
        $parent_key = $condition->getPluginId() . ':' . $configuration['depth'];
        $organized_conditions[$parent_key] = [
          'operator' => 'OR',
          'conditions' => [],
        ];
      } elseif (!empty($configuration['parent'])) {
        $parent_key = $configuration['parent'] . ':' . ($configuration['depth'] - 1);
        $organized_conditions[$parent_key]['conditions'][] = $condition;
      } else {
        $organized_conditions['ungrouped']['conditions'][] = $condition;
      }
    }
    // @todo test if missing target
    // @todo test if target key is set but wrong entity type.
    $boolean = !($base_operator === 'AND');
    foreach ($organized_conditions as $condition_group) {
      $result = $this->evaluateConditionGroup($condition_group['conditions'], $condition_group['operator'], $targets);
      if ($result === $boolean) {
        return $boolean;
      }
    }
    return !$boolean;
  }

  /**
   * @param array $conditions
   * @param string $operator
   * @param array $targets
   *
   * @return bool
   */
  private function evaluateConditionGroup(array $conditions, string $operator, array $targets): bool {
    assert($operator === 'AND' || $operator === 'OR');
    $boolean = !($operator === 'AND');
    foreach ($conditions as $condition) {
      $negated = $condition->getConfiguration()['negate_condition'] ?? FALSE;
      $target_entity_type = $condition->getEntityTypeId();
      $result = $condition->evaluate($targets[$target_entity_type]);
      $result = $negated ? !$result : $result;
      if ($result === $boolean) {
        return $boolean;
      }
    }
    return !$boolean;
  }
}
