<?php

namespace Drupal\election;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Election ballot vote entity.
 *
 * @see \Drupal\election\Entity\ElectionBallotVote.
 */
class ElectionBallotVoteAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\election\Entity\ElectionBallotVoteInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished election ballot vote entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published election ballot vote entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit election ballot vote entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete election ballot vote entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add election ballot vote entities');
  }


}
