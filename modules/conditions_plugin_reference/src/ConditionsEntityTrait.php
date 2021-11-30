<?php

namespace Drupal\conditions_plugin_reference;

use Drupal\conditions_plugin_reference\Plugin\Commerce\Condition\ConditionInterface;

trait ConditionsEntityTrait {
  /**
   * {@inheritdoc}
   */
  public function getConditions() {
    $conditions = [];
    foreach ($this->get('conditions') as $field_item) {
      /** @var \Drupal\commerce\Plugin\Field\FieldType\PluginItemInterface $field_item */
      $condition = $field_item->getTargetInstance();
      $conditions[] = $condition;
    }
    return $conditions;
  }

  /**
   * {@inheritdoc}
   */
  public function setConditions(array $conditions) {
    $this->set('conditions', []);
    foreach ($conditions as $condition) {
      if ($condition instanceof ConditionInterface) {
        $this->get('conditions')->appendItem([
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
  public function getConditionOperator() {
    return $this->get('condition_operator')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setConditionOperator($condition_operator) {
    $this->set('condition_operator', $condition_operator);
    return $this;
  }
}
