<?php

namespace Drupal\election_fptp\Plugin\ElectionVotingMethodPlugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\election\Entity\ElectionCandidate;
use Drupal\election\Entity\ElectionPost;
use Drupal\election\Plugin\ElectionVotingMethodPluginBase as PluginElectionVotingMethodPluginBase;

/**
 * Single transferable vote.
 *
 * @ElectionVotingMethodPlugin(
 *   id = "fptp",
 *   label = @Translation("First past the post (single non-transferable)"),
 * )
 */
class FirstPastThePost extends PluginElectionVotingMethodPluginBase {

  /**
   * {@inheritdoc}
   */
  public function countPosition(ElectionPost $electionPost, array $candidates, array $options = []) {
    $result = [];
    $countPerCandidate = [];
    $voters = [];
    $log = [];

    $vacancies = $electionPost->vacancies->value;

    // Count candidates:
    $result['count_candidates_all'] = count($electionPost->getCandidates(NULL, FALSE));
    $result['count_candidates_published'] = count($electionPost->getCandidates(NULL, TRUE));
    $result['count_candidates_included'] = count($candidates);

    // Initialise ballot stats:
    $result['count_total_ballots'] = 0;
    $result['count_total_votes'] = 0;
    $result['count_total_abstentions'] = 0;
    $ballots = $electionPost->getBallots(TRUE);
    foreach ($ballots as $ballot) {
      $voters[] = $ballot->get('user_id')->getValue();
      $result['count_total_ballots']++;

      if ($ballot->abstention->value == TRUE) {
        $result['count_total_abstentions']++;
      } else {
        $votes = $ballot->getVotes();
        foreach ($votes as $vote) {
          if ($vote->ranking == 1) {
            $result['count_total_votes']++;
            $candidate_id = $ballot->candidate_id->entity->id();
            if (!isset($options[$candidate_id])) {
              $countPerCandidate[$candidate_id] = 0;
            }
            $countPerCandidate[$candidate_id]++;
          }
        }
      }
    }

    arsort($options);

    // Group count values (in case of tiebreaker):
    $elected = [];
    $defeated = [];
    $groupedCounts = [];
    foreach ($countPerCandidate as $candidate_id => $count) {
      $candidate = ElectionCandidate::load($candidate_id);
      if (!isset($groupedCounts[$count])) {
        $groupedCounts[$count] = [];
      }
      $groupedCounts[$count][] = $candidate;
    }

    foreach ($groupedCounts as $count => $candidates) {
      $message = '@countCandidates candidates with @countVotes votes';

      $candidate = NULL;
      $vacanciesLeft = $vacancies - count($elected);
      $tiebreak = count($candidates) > 0;

      if ($vacanciesLeft > 0) {
        $message .= ', @vacanciesLeft vacancies left';
      } else {
        $message .= ', no vacancies left';
      }

      // We do the tiebreaker every time even if there's no tie:
      $tiebreaker = $this->getConfiguration()['tiebreaker'] ?: 'none';
      if ($tiebreaker == 'random') {
        if ($tiebreak) {
          $message .= ', selecting candidates at random to break tie';
        }

        // @todo truly (cryptographically) random shuffler:
        shuffle($candidates);
        $winners = array_slice($candidates, 0, $vacanciesLeft);
        $elected = array_merge($elected, $winners);

        $losers = array_slice($candidates, count($winners));
        $defeated = array_merge($elected, $losers);
      } else {
        $message .= ', tie but no tiebreaking rule provided so no candidates awarded and counting stopped.';
        $log[] = t($message, [
          '@countCandidates' => count($candidates),
          '@countVotes' => $count,
          '@vacanciesLeft' => $vacanciesLeft,
        ]);
        break;
      }

      $log[] = t($message, [
        '@countCandidates' => count($candidates),
        '@countVotes' => $count,
        '@vacanciesLeft' => $vacanciesLeft,
      ]);
    }

    $result['count_candidates_elected'] = count($elected);
    $result['count_candidates_defeated'] = count($defeated);

    $result['count_timestamp'] = \Drupal::time()->getRequestTime();
    $result['count_method'] = "";
    $result['count_results_text'] = $this->getOutputText($log, $elected, $defeated, $countPerCandidate, []);
    $result['count_results_html'] = $this->getOutputHtml($log, $elected, $defeated, $countPerCandidate, []);
    $result['count_total_voters'] = count($voters);

    $result['count_log'] = implode("\r\n", $log);
    return $result;
  }

  public function getOutputText($log, $elected, $defeated, $countPerCandidate, $options) {
  }

  public function getOutputHtml($log, $elected, $defeated, $countPerCandidate, $options) {
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['tiebreaker'] = '';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
  }
}
