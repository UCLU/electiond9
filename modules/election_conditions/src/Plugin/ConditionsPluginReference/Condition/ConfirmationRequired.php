<?php

namespace Drupal\election_conditions\Plugin\ConditionsPluginReference\Condition;

use Drupal\election_conditions\Plugin\ElectionConditionBase;

/**
 * Require an action before voting
 *
 * In this case, a text is shown and checkbox, but could e.g. be a self-definition question
 *
 * @ConditionsPluginReference(
 *   id = "election_confirmation_required",
 *   condition_types = {
 *     "election",
 *   },
 *   label = @Translation("Confirmation required"),
 *   display_label = @Translation("Confirmation required"),
 *   category = @Translation("Actions"),
 *   weight = 0,
 * )
 */
class ConfirmationRequired extends ElectionConditionBase {
}
