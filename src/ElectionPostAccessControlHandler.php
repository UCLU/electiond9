<?php

namespace Drupal\election;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityType;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;

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

    $electionAccess = new ElectionAccessControlHandler($entity->getElection()->getEntityType());

    switch ($operation) {

      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished election post entities');
        }

        return AccessResult::allowedIfHasPermission($account, 'view published election post entities');

      case 'update':
        if ($entity && $entity->isOpenOrPartiallyOpen('voting') && !$account->hasPermission('bypass running election lock')) {
          // Deny deleting running elections.
          // Use the permission 'bypass running election lock' to bypass this.
          return AccessResult::forbidden();
        }

        return $electionAccess->checkAccess($entity->getElection(), $operation, $account);

      case 'delete':
        // Cannot delete if cannot edit election
        if ($electionAccess->checkAccess($entity->getElection(), 'update', $account)->isForbidden()) {
          return AccessResult::forbidden();
        }

        // Cannot delete if ballots exist and they don't have permission
        $ballots = $entity->countBallots(TRUE);
        if ($ballots > 0) {
          if (!\Drupal::currentUser()->hasPermission('delete posts with ballots')) {
            \Drupal::messenger()->addWarning(t('Cannot delete this post because votes have already been cast.'));
          }
          return AccessResult::allowedIfHasPermission($account, 'delete posts with ballots');
        } else {
          return AccessResult::allowedIfHasPermission($account, 'delete posts without ballots');
        }
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

  /**
   * {@inheritdoc}
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    switch ($field_definition->getName()) {
      case 'status':
        return AccessResult::allowedIfHasPermission($account, 'edit election post entities');
    }

    return parent::checkFieldAccess($operation, $field_definition, $account, $items);
  }
}
