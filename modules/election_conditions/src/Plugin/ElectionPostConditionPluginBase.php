<?php

namespace Drupal\election_conditions\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\conditions_plugin_reference\Plugin\ConditionsPluginReference\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\election\Entity\ElectionPost;

/**
 * Base class for Election post condition plugin plugins.
 */
abstract class ElectionPostConditionPluginBase extends ConditionBase implements ElectionPostConditionPluginInterface {

  public function requiredParameters(): array {
    return [
      'account' => AccountInterface::class,
      'phase' => 'string',
    ];
  }

  /**
   * Return true or false if access.
   *
   * @param string $phase
   *
   * @return boolean
   */
  public function evaluate(EntityInterface $entity, $parameters = [], $return_reasons = FALSE) {
    $this->assertParameters($parameters);
  }

  /**
   * Get cache tags that should lead to re-calculating this condition result.
   *
   * E.g. by default it should be re-calculated only if the post or account data changes.
   *
   * @param Drupal\election\Entity\ElectionPost $post
   *   Post being checked.
   * @param Drupal\Core\Session\AccountInterface $account
   *   User account being checked.
   *
   * @return array
   *   Array of cache tags.
   */
  public function getCacheTagsForEligibility(ElectionPost $post, AccountInterface $account) {
    $tags = [];

    // Could e.g. get profile tags, or not get election and user

    return $tags;
  }

  public function getCacheContextsForEligibility(ElectionPost $post, AccountInterface $account) {
  }
}
