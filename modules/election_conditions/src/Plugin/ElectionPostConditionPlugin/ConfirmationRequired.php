<?php

<?php

namespace Drupal\election_conditions\Plugin;

/**
 * Require an action before voting
 *
 * In this case, a text is shown and checkbox, but could e.g. be a self-definition question
 *
 * @ConditionsPluginReference(
 *   id = "election_confirmation_required",
 *   label = @Translation("User role(s)"),
 *   display_label = @Translation("User has specific user role(s)"),
 *   category = @Translation("Actions"),
 *   weight = 0,
 * )
 */
class ConfirmationRequired extends ElectionPostConditionPluginBase {
}
