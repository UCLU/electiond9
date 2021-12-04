<?php

namespace Drupal\election\Service;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Session\AccountInterface;
use Drupal\election\ElectionPostConditionChecker;
use Drupal\election\Entity\Election;
use Drupal\election\Entity\ElectionBallot;
use Drupal\election\Entity\ElectionCandidate;
use Drupal\election\Entity\ElectionPostInterface;
use \Drupal\user\Entity\User;

/**
 * Class ElectionPostEligibilityChecker.
 */
class ElectionPostEligibilityChecker {

  public static function evaluateEligibility(AccountInterface $account, ElectionPostInterface $election_post, string $phase, $includePhaseStatus = FALSE, $refresh = FALSE) {
    $requirements = static::evaluateEligibilityRequirements($account, $election_post, $phase, $includePhaseStatus, $refresh);
    return static::checkRequirementsForEligibility($requirements);
  }

  public static function checkRequirementsForEligibility($requirements) {
    return !in_array(FALSE, array_values($requirements));
  }

  /**
   * Return either TRUE, or FALSE or an array of reasons why a user cannot vote
   *
   * Uses drupal cache and only generates these
   *
   * @param User $account
   * @param ElectionPost $post
   * @param string $phase see Election::ELECTION_PHASES
   * @param bool $includePhaseStatus
   *   Whether to include the phase being opened or closed as an elibility criteria.
   *   Set TRUE for hard checks like access,
   *   FALSE for soft checks where you need to differentiate.
   * @return boolean|array
   */
  public static function evaluateEligibilityRequirements(AccountInterface $account, ElectionPostInterface $election_post, string $phase, $includePhaseStatus = FALSE, $refresh = FALSE) {
    // Sort out caching
    $cid_components = [
      'evaluateEligibilityRequirements',
      $election_post->id(),
      $account->id(),
      $phase,
      ($includePhaseStatus ? 'ps' : ''),
    ];
    $cid = implode(':', $cid_components);

    // Return cache data if we have it and we're not refreshing:
    if (!$refresh && $cache = \Drupal::cache('election')->get($cid)) {
      $requirements = $cache->data;
    } else {
      $account = User::load($account->id());
      $election = $election_post->getElection();

      $requirements = [];

      $requirements['election_published'] = $election->isPublished();
      if ($requirements['election_published']) {
        $requirements['election_post_published'] = $election_post->isPublished();
      }

      // Check if have 'create nominations' or equivalent permission, and allow access to nomination (but not voting) if so
      // TODO

      $electionPhases = $election->getEnabledPhases();
      $requirements[$phase . '_enabled'] = in_array($phase, $electionPhases);

      if ($includePhaseStatus) {
        $electionStatus = $election->getPhaseStatuses();
        $requirements[$phase . '_open_election'] = $electionStatus[$phase] == 'open';

        if ($requirements[$phase . '_open_election']) {
          $postStatus = $election_post->getPhaseStatuses($phase);
          $requirements[$phase . '_open_election_post'] = $postStatus[$phase] == 'open';
        }
      }

      // Check if logged in:
      // @TODO is there a use case for anonymous users voting?
      $requirements['logged_in'] = !\Drupal::currentUser()->isAnonymous();

      if ($phase == 'interest') {
        $requirements['permission_interest'] = $account->hasPermission('express interest in posts');
        if ($requirements['logged_in']) {
          if ($election_post->get('limit_to_one_nomination_per_user')->value && static::interestExists($account, $election_post)) {
            $requirements['not_already_interest'] = static::ballotExists($account, $election_post);
          }
        }
      }

      if ($phase == 'nominations') {
        $requirements['permission_nominations'] = $account->hasPermission('nominate for posts');
        if ($requirements['logged_in']) {
          if ($election_post->get('limit_to_one_nomination_per_user')->value && static::nominationExists($account, $election_post)) {
            $requirements['not_already_nominations'] = static::ballotExists($account, $election_post);
          }
        }
      }

      if ($phase == 'voting') {
        $requirements['permission_voting'] = $account->hasPermission('vote');

        if ($requirements['logged_in']) {
          $requirements['not_already_voting'] = static::ballotExists($account, $election_post);
        }

        // @todo check number of candidates
        $candidates = $election_post->getCandidatesForVoting();
        $requirements['enough_candidates'] = count($candidates) > 0;
      }

      if (\Drupal::moduleHandler()->moduleExists('election_conditions')) {
        $conditions = $election_post->getConditions($phase);

        // Check all  conditions:
        if (count($conditions) > 0) {
          foreach ($conditions as $condition) {
            $conditionRequirements = $condition->evaluateRequirements($election_post, $account, ['phase' => $phase]);
            if ($conditionRequirements && count($conditionRequirements) > 0) {
              $requirements = array_merge($requirements, $conditionRequirements);
            }
          }
        }
      }

      // @todo $tags = get cache tags for conditions
      // @todo get specirfic tags so we don't have to rely on entity changes entirely
      // For now this. We want this to be more intelligent.
      $tags = $election_post->getUserEligibilityCacheTags($account, $phase);

      \Drupal::cache('election')->set($cid, $requirements, Cache::PERMANENT, $tags);
    }
    return $requirements;
  }

  public static function ballotExists($account, $election_post) {
    $ballots = ElectionBallot::loadByUserAndPost($account, $election_post);
    return count($ballots) > 0;
  }

  public static function nominationExists($account, $election_post) {
    $nominations = ElectionCandidate::loadByUserAndPost($account, $election_post, ['hopeful']);
    return count($nominations) > 0;
  }

  public static function interestExists($account, $election_post) {
    $nominations = ElectionCandidate::loadByUserAndPost($account, $election_post, ['interest']);
    return count($nominations) > 0;
  }


  /**
   * For all posts currently open or soon to be open, refresh the user's eligibility
   *
   * @param [type] $account
   */
  public static function recalculateEligibilityForUser($account, $election = NULL) {
    // Get all open or soon to be open elections
    $elections = [$election]; // TODO

    foreach ($elections as $election) {
      $posts = []; // TODO
      foreach ($posts as $post) {
        ElectionPostEligibilityChecker::recalculateEligibility($account, $post);
      }
    }
  }

  /**
   * For a single post, refresh all users' eligibility.
   *
   * Only users with the roles specified in the settings will be checked, to improve performance.
   *
   * This should be called:
   * - When a post's conditions change
   * - When an election status changes (i.e. if it is edited, or if an election becomes open)
   * - maybe by CRON sometimes
   *
   * @param [type] $account
   * @return void
   */
  public static function recalculateEligibilityForPost($post) {
    $roles = \Drupal::config('election.settings')->get('cache_eligibility_roles');
    if (count($roles) == 0) {
      return FALSE;
    }
    $ids = [];
    foreach ($roles as $role) {
      $ids = $ids + \Drupal::entityQuery('user')
        ->condition('status', 1)
        ->condition('roles', $roles, 'IN')
        ->execute();
    }
    $ids = array_unique($ids);
    $users = User::loadMultiple($ids);

    foreach ($users as $account) {
      ElectionPostEligibilityChecker::recalculateEligibility($account, $post);
    }
  }

  /**
   * Tell Drupal to recalculate eligibility to override cache.
   *
   * @param User $account
   * @param ElectionPostInterface $post
   * @return void
   */
  public static function recalculateEligibility($account, $post) {
    foreach (Election::ELECTION_PHASES as $phase) {
      ElectionPostEligibilityChecker::evaluateEligibility($account, $post, $phase, TRUE, TRUE);
    }
    return TRUE;
  }
}
