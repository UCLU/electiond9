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
   * @param boolean $return_reasons
   * @return boolean|array
   */
  public static function checkEligibility(AccountInterface $account, ElectionPostInterface $election_post, string $phase, $includePhaseStatus = FALSE, $return_reasons = FALSE, $refresh = FALSE) {
    $data = &drupal_static(__METHOD__);
    $cid = 'election:election_post:' . $election_post->id() . ':' . $account->id() . ':' . $phase . ':' . ($includePhaseStatus ? 'with_phase_status' : 'no_phase_status') . ':' . ($return_reasons ? 'reasons' : 'boolean');

    if (!$refresh && $cache = \Drupal::cache('election_bin')->get($cid)) {
      $data = $cache->data;
    } else {
      $account = User::load($account->id());

      $election = $election_post->getElection();

      $reasons = [];
      $eligible = TRUE;

      if (!$election_post->isPublished()) {
        $eligible = FALSE;
        $reasons[] = 'election_post_not_published';
      }

      if (!$election->isPublished()) {
        $eligible = FALSE;
        $reasons[] = 'election_not_published';
      }

      $electionPhases = $election->getEnabledPhases();
      if (!in_array($phase, $electionPhases)) {
        $eligible = FALSE;
        $reasons[] = $phase . '_not_enabled';
      }

      if ($includePhaseStatus) {
        $electionStatus = $election->getPhaseStatuses();
        if ($electionStatus[$phase] != 'open') {
          $eligible = FALSE;
          $reasons[] = $phase . '_not_open_election';
        }

        $postStatus = $election_post->getPhaseStatuses($phase);
        if ($postStatus[$phase] != 'open') {
          $eligible = FALSE;
          $reasons[] = $phase . '_not_open_election_post';
        }
      }

      // Check if have 'create nominations' or equivalent permission, and allow access to nomination (but not voting) if so
      // TODO

      if ($phase == 'voting' && !$account->hasPermission('add election ballot entities')) {
        $eligible = FALSE;
        $reasons[] = 'no_permission_voting';
      }

      // Check if logged in:
      // @TODO is there a use case for anonymous users voting?
      if (\Drupal::currentUser()->isAnonymous()) {
        $eligible = FALSE;
        $reasons[] = 'not_logged_in';
      } else {
        if ($phase == 'voting' && static::ballotExists($account, $election_post)) {
          $eligible = FALSE;
          $reasons[] = 'already_' . $phase;
        }
        if ($phase == 'nominations' && $election_post->get('limit_to_one_nomination_per_user')->value && static::nominationExists($account, $election_post)) {
          $eligible = FALSE;
          $reasons[] = 'already_' . $phase;
        }

        if (\Drupal::moduleHandler()->moduleExists('election_conditions')) {
          $conditions = $election_post->getConditions($phase);

          // Check all  conditions:
          if (count($conditions) > 0) {
            foreach ($conditions as $condition) {
              $conditionReason = $condition->evaluate($election_post, $account, $phase);
              if ($conditionReason && count($conditionReason) > 0) {
                $reasons = array_merge($reasons, $conditionReason);
                $eligible = FALSE;
              }
            }
          }
        }
      }

      if ($return_reasons) {
        $data = $reasons;
      } else {
        $data = $eligible;
      }

      // @todo $tags = get cache tags for conditions
      // @todo get specirfic tags so we don't have to rely on entity changes entirely
      // For now this. We want this to be more intelligent.
      $tags = $election_post->getUserEligibilityCacheTags($account, $phase);

      \Drupal::cache('election_bin')->set($cid, $data, Cache::PERMANENT, $tags);
    }
    return $data;
  }

  public static function ballotExists($account, $election_post) {
    $ballots = ElectionBallot::loadByUserAndPost($account, $election_post);
    return count($ballots) > 0;
  }

  public static function nominationExists($account, $election_post) {
    $nominations = ElectionCandidate::loadByUserAndPost($account, $election_post);
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
      $boolean = ElectionPostEligibilityChecker::checkEligibility($account, $post, $phase, TRUE, FALSE, TRUE);
      $reasons = ElectionPostEligibilityChecker::checkEligibility($account, $post, $phase, TRUE, TRUE, TRUE);
    }
    return TRUE;
  }
}
