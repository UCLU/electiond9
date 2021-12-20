<?php

namespace Drupal\complex_conditions\Plugin\ComplexConditions\Condition;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Condition\ConditionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the interface for conditions.
 */
interface ComplexConditionInterface extends ConditionInterface, ConfigurableInterface, DependentPluginInterface, PluginFormInterface, PluginInspectionInterface {

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

  /**
   * Evaluates the condition's requirements and returns them as an array.
   *
   * Should always call $this->dispatchRequirementEvents($requirements)
   * after pulling together the requirements.
   *
   * @param EntityInterface $entity
   * @param AccountInterface $account
   * @param array $parameters
   *   Parameters for the condition.
   *
   * @return array
   *   Array of ConditionRequirement objects.
   */
  public function evaluateRequirements(EntityInterface $entity, AccountInterface $account, $parameters = []);

  public function requiredParameters();

  public function assertParameters(array $parameters);
}
