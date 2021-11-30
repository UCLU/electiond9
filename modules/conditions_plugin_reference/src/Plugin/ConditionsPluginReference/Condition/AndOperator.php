<?php

declare(strict_types=1);

namespace Drupal\conditions_plugin_reference\Plugin\ConditionPluginReference\Condition;

use Drupal\conditions_plugin_reference\Plugin\Commerce\Condition\ConditionBase;

/**
 * Provides the weight condition for shipments.
 *
 * @ConditionPluginReference(
 *   id = "conditions_plugin_reference_and_operator",
 *   label = @Translation("And Operator"),
 *   category = @Translation("Conditions"),
 * )
 */
final class AndOperator extends ConditionBase {

  /**
   * @inheritDoc
   */
  public function evaluate($entity, $parameters = [], $return_reasons = FALSE) {
    // @todo find child via config? evaluate there?
    return TRUE;
  }
}
