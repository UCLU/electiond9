<?php

namespace Drupal\election;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\election\Entity\ElectionPost;

/**
 * Access controller for the Election ballot entity.
 *
 * @see \Drupal\election\Entity\ElectionBallot.
 */
class ElectionBallotAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\election\Entity\ElectionBallotInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished election ballot entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published election ballot entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit election ballot entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete election ballot entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $eligibilityService = \Drupal::service('election.post_eligibility_checker');
    $context_provider = \Drupal::service('election.election_route_context');

    $contexts = $context_provider->getRuntimeContexts(['election_post']);

    if (!isset($contexts['election_post']) || !$contexts['election_post']) {
      return AccessResult::neutral();
    }
    $election_post = ElectionPost::load($contexts['election_post']->getContextValue());

    $eligible = $eligibilityService->evaluateEligibility($account, $election_post, 'voting', TRUE, TRUE);
    return AccessResult::allowedIf($eligible);
  }
}
