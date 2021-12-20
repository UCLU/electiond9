<?php

namespace Drupal\complex_conditions;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Represents a condition group.
 *
 * Meant to be instantiated directly.
 */
final class ConditionGroup {

  /**
   * The conditions.
   *
   * @var \Drupal\complex_conditions\Plugin\ComplexConditions\Condition\ConditionInterface[]
   */
  protected $conditions;

  /**
   * The operator.
   *
   * Possible values: AND, OR.
   *
   * @var string
   */
  protected $operator;

  /**
   * Constructs a new ConditionGroup object.
   *
   * @param \Drupal\complex_conditions\Plugin\ComplexConditions\Condition\ConditionInterface[] $conditions
   *   The conditions.
   * @param string $operator
   *   The operator. Possible values: AND, OR.
   *
   * @throws \InvalidArgumentException
   *   Thrown when an invalid operator is given.
   */
  public function __construct(array $conditions, string $operator) {
    if (!in_array($operator, ['AND', 'OR'])) {
      throw new \InvalidArgumentException(sprintf('Invalid operator "%s" given, expecting "AND" or "OR".', $operator));
    }

    $this->conditions = $conditions;
    $this->operator = $operator;
  }

  /**
   * Gets the conditions.
   *
   * @return \Drupal\complex_conditions\Plugin\ComplexConditions\Condition\ConditionInterface[]
   *   The conditions.
   */
  public function getConditions(): array {
    return $this->conditions;
  }

  /**
   * Gets the operator.
   *
   * @return string
   *   The operator. Possible values: AND, OR.
   */
  public function getOperator(): string {
    return $this->operator;
  }

  /**
   * Evaluates the condition group.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   TRUE if the condition group has passed, FALSE otherwise.
   */
  public function evaluate(EntityInterface $entity, AccountInterface $account, array $parameters = []): bool {
    if (empty($this->conditions)) {
      return TRUE;
    }

    $boolean = $this->operator == 'AND' ? FALSE : TRUE;
    foreach ($this->conditions as $condition) {
      if ($condition->evaluate($entity, $account) == $boolean) {
        return $boolean;
      }
    }

    return !$boolean;
  }
}
