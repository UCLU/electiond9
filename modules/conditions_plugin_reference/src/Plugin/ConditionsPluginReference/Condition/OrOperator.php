<?php

namespace Drupal\conditions_plugin_reference\Plugin\ConditionsPluginReference\Condition;

use Drupal\conditions_plugin_reference\Plugin\Condition\ConditionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides the weight condition for shipments.
 *
 * @ConditionsPluginReference(
 *   id = "conditions_plugin_reference_or_operator",
 *   label = @Translation("Or Operator"),
 *   category = @Translation("Conditions"),
 * )
 */
final class OrOperator extends ConditionBase {

  /**
   * @inheritDoc
   */
  public function evaluate($entity, $parameters = []) {
    // @todo find child via config? evaluate there?
    return TRUE;
  }
}
