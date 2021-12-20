<?php

namespace Drupal\complex_conditions\Plugin\ComplexConditions\Condition;

use Drupal\complex_conditions\Plugin\ComplexConditions\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides the weight condition for shipments.
 *
 * @ComplexConditions(
 *   id = "complex_conditions_and_operator",
 *   label = @Translation("And group (all conditions must be true)"),
 *   category = @Translation("Conditions"),
 * )
 */
final class AndOperator extends ConditionBase {

  /**
   * @inheritDoc
   */
  public function evaluate(EntityInterface $entity, AccountInterface $account, $parameters = []) {
    // @todo find child via config? evaluate there?
    return TRUE;
  }
}
