<?php

namespace Drupal\election;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Election post entity.
 *
 * @see \Drupal\election\Entity\ElectionPost.
 */
class ElectionPostAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\election\Entity\ElectionPostInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished election post entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published election post entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit election post entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete election post entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add election post entities');
  }


}
