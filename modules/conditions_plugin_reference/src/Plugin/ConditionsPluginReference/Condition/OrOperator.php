<?php

namespace Drupal\conditions_plugin_reference\Plugin\ConditionsPluginReference\Condition;

use Drupal\conditions_plugin_reference\Plugin\ConditionsPluginReference\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides the weight condition for shipments.
 *
 * @ConditionsPluginReference(
 *   id = "conditions_plugin_reference_or_operator",
 *   label = @Translation("Or group (any condition must be true)"),
 *   category = @Translation("Conditions"),
 * )
 */
final class OrOperator extends ConditionBase {

  /**
   * @inheritDoc
   */
  public function evaluate(EntityInterface $entity, AccountInterface $account, $parameters = []) {
    // @todo find child via config? evaluate there?
    return TRUE;
  }
}
