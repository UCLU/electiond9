<?php

namespace Drupal\election\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\election\Entity\ElectionPost;

/**
 * Base class for Election post condition plugin plugins.
 */
abstract class ElectionPostConditionPluginBase extends PluginBase implements ElectionPostConditionPluginInterface {

  /**
   * Return true or false if access.
   *
   * @param ElectionPost $post
   * @param AccountInterface $account
   * @param string $operation
   *
   * @return boolean
   */
  public function evaluate(ElectionPost $post, AccountInterface $account, string $operation) {
  }

  public function getExplanations(ElectionPost $post, AccountInterface $account) {
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

    // by default get post, election and user tags

    // But could e.g. get profile tags, or not get election and user

    return $tags;
  }
}
