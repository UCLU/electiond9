<?php

namespace Drupal\conditions_plugin_reference;

use Drupal\conditions_plugin_reference\Plugin\ConditionsPluginReference\Condition\ConditionInterface;

trait ConditionsEntityTrait {
  /**
   * {@inheritdoc}
   */
  public function getConditionsForField($field_name) {
    $conditions = [];
    foreach ($this->get($field_name) as $field_item) {
      /** @var \Drupal\conditions_plugin_reference\Plugin\Field\FieldType\PluginItemInterface $field_item */
      $condition = $field_item->getTargetInstance();
      $conditions[] = $condition;
    }
    return $conditions;
  }

  /**
   * {@inheritdoc}
   */
  public function setConditionsForField(array $conditions, $field_name) {
    $this->set($field_name, []);
    foreach ($conditions as $condition) {
      if ($condition instanceof ConditionInterface) {
        $this->get($field_name)->appendItem([
          'target_plugin_id' => $condition->getPluginId(),
          'target_plugin_configuration' => $condition->getConfiguration(),
        ]);
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConditionOperator($conditions_field_name) {
    return $this->get($conditions_field_name . '_operator')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setConditionOperator($condition_operator, $conditions_field_name) {
    $this->set($conditions_field_name . '_operator', $condition_operator);
    return $this;
  }
}
