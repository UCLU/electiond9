<?php

namespace Drupal\election_conditions\Plugin;

/**
 * Condition.
 *
 * @ConditionsPluginReference(
 *   id = "election_group_role",
 *   label = @Translation("User role(s)"),
 *   display_label = @Translation("User has specific user role(s)"),
 *   category = @Translation("Groups"),
 *   weight = 0,
 * )
 */
class GroupRole extends ElectionPostConditionPluginBase {
}
