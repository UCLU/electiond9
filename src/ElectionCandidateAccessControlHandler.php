<?php

namespace Drupal\election;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\election\Entity\ElectionPost;

/**
 * Access controller for the Election candidate entity.
 *
 * @see \Drupal\election\Entity\ElectionCandidate.
 */
class ElectionCandidateAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\election\Entity\ElectionCandidateInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished election candidate entities');
        }

        return AccessResult::allowedIfHasPermission($account, 'view published election candidate entities');

      case 'update':
        $editPermission = $account->hasPermission('edit election candidate entities');
        if ($editPermission) {
          return AccessResult::allowed();
        }

        $isOwner = $entity->getOwnerId() == $account->id();
        if (!$isOwner) {
          return AccessResult::forbidden();
        }

        $postEditingRule = $entity->getElectionPost()->allow_candidate_editing->value;
        switch ($postEditingRule) {
          case 'true':
            return AccessResult::allowed();

          case 'false':
            \Drupal::messenger()->addWarning(t('Cannot edit nomination once created.'));
            return AccessResult::forbidden();

          case 'nominations':
            // @todo deal with interest...
            $canEdit = $entity->checkStatusForPhase('nominations', 'open');
            if (!$canEdit) {
              \Drupal::messenger()->addWarning(t('Cannot edit if nominations are closed.'));
            }
            return AccessResult::allowedIf($canEdit);

          case 'no_votes':
            $canEdit = $entity->countBallotVotes(TRUE) == 0;
            if (!$canEdit) {
              \Drupal::messenger()->addWarning(t('Cannot edit once votes are cast.'));
            }
            return AccessResult::allowedIf($canEdit);
        }

      case 'delete':
        $votesCount = $entity->countBallotVotes(TRUE);
        if ($votesCount > 0) {
          if (!\Drupal::currentUser()->hasPermission('delete candidates with votes')) {
            \Drupal::messenger()->addWarning(t('Cannot delete this candidate because votes have already been cast.'));
          }
          return AccessResult::allowedIfHasPermission($account, 'delete candidates with votes');
        } else {
          return AccessResult::allowedIfHasPermission($account, 'delete candidates without votes');
        }
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    dd($account, $context, $entity_bundle);
    $createPermission = $account->hasPermission('add election candidate entities');
    if ($createPermission) {
      return AccessResult::allowed();
    }

    $context_provider = \Drupal::service('election.election_route_context');
    $contexts = $context_provider->getRuntimeContexts(['election_post']);
    if (!isset($contexts['election_post']) || !$contexts['election_post']) {
      return AccessResult::neutral();
    }
    $election_post = ElectionPost::load($contexts['election_post']->getContextValue());

    $eligibilityService = \Drupal::service('election.post_eligibility_checker');

    if ($election_post->checkStatusForPhase('interest', 'open')) {
      $canInterest = $account->hasPermission('express interest in posts');
      $eligibleInterest = $eligibilityService->evaluateEligibility($account, $election_post, 'phase', TRUE);
      if ($canInterest && $eligibleInterest) {
        return AccessResult::allowed();
      }
    }

    if ($election_post->checkStatusForPhase('nominations', 'open')) {
      $canNominate = $account->hasPermission('express interest in posts');
      $eligibleNominate = $eligibilityService->evaluateEligibility($account, $election_post, 'phase', TRUE);
      if ($canNominate && $eligibleNominate) {
        return AccessResult::allowed();
      }
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    switch ($field_definition->getName()) {
      case 'status':
        return AccessResult::allowedIfHasPermission($account, 'edit election candidate entities');
    }

    return parent::checkFieldAccess($operation, $field_definition, $account, $items);
  }
}
