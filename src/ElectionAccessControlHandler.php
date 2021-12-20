<?php

namespace Drupal\election;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\election\Entity\ElectionInterface;

/**
 * Access controller for the Election entity.
 *
 * @see \Drupal\election\Entity\Election.
 */
class ElectionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\election\Entity\ElectionInterface $entity */

    switch ($operation) {

      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished elections');
        }

        return AccessResult::allowedIfHasPermission($account, 'view published elections');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit elections');

      case 'delete':
        if ($entity && $entity->isOpenOrPartiallyOpen('voting') && !$account->hasPermission('bypass running election lock')) {
          // Deny deleting running elections.
          // Use the permission 'bypass running election lock' to bypass this.
          return AccessResult::forbidden();
        }
        return AccessResult::allowedIfHasPermission($account, 'delete elections');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add elections');
  }
}
