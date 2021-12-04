<?php

namespace Drupal\conditions_plugin_reference\Plugin\ConditionsPluginReference\Condition;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the interface for conditions.
 */
interface ConditionInterface extends ConfigurableInterface, DependentPluginInterface, PluginFormInterface, PluginInspectionInterface {

  /**
   * Gets the condition label.
   *
   * @return string
   *   The condition label.
   */
  public function getLabel();

  /**
   * Gets the condition display label.
   *
   * Shown in the condition UI when enabling/disabling a condition.
   *
   * @return string
   *   The condition display label.
   */
  public function getDisplayLabel();

  public function getConditionSummary();

  /**
   * Evaluates the condition.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   TRUE if the condition has been met, FALSE otherwise.
   */
  public function evaluate(EntityInterface $entity, AccountInterface $account, $parameters = []);

  public function getReasons(EntityInterface $entity, AccountInterface $account, $parameters = []);

  public function requiredParameters();

  public function assertParameters(array $parameters);
}
