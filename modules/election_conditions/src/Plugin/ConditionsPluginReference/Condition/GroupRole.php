<?php

namespace Drupal\election_conditions\Plugin\ConditionsPluginReference\Condition;

use Drupal\election_conditions\Plugin\ElectionConditionBase;

/**
 * Condition.
 *
 * @ConditionsPluginReference(
 *   id = "election_group_role",
 *   condition_types = {
 *     "election",
 *   },
 *   label = @Translation("User has role in group"),
 *   display_label = @Translation("User has role in group "),
 *   category = @Translation("Group memberships"),
 *   weight = 0,
 * )
 */
class GroupRole extends ElectionConditionBase {
}
