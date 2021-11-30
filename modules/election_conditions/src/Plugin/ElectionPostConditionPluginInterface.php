<?php

namespace Drupal\election_conditions\Plugin;

use Drupal\conditions_plugin_reference\Plugin\ConditionsPluginReference\Condition\ConditionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\election\Entity\ElectionPost;

/**
 * Defines an interface for Election post condition plugin plugins.
 */
interface ElectionPostConditionPluginInterface extends ConditionInterface {

  public function getCacheTagsForEligibility(ElectionPost $post, AccountInterface $account);

  public function getCacheContextsForEligibility(ElectionPost $post, AccountInterface $account);
}
