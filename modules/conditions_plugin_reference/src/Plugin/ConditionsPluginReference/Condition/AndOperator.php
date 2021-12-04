<?php

namespace Drupal\conditions_plugin_reference\Plugin\ConditionsPluginReference\Condition;

use Drupal\conditions_plugin_reference\Plugin\ConditionsPluginReference\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides the weight condition for shipments.
 *
 * @ConditionsPluginReference(
 *   id = "conditions_plugin_reference_and_operator",
 *   label = @Translation("And Operator"),
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
