<?php



namespace Drupal\election_ballot_export\Service;

use Drupal\election\Entity\ElectionPost;

/**
 * Class BallotExportService.
 */
class BallotExportService {

  /**
   * Generate a BLT file of results, for a single election post.
   *
   * @param object $post
   *   The election post entity.
   * @param array $options
   *   Optional: an array of additional export options.
   *
   * @return string
   *   The URI of the generated results file.
   */
  public function exportPost(ElectionPost $electionPost, array $options = []) {
    // Export the results into a ballot file. We specify a temporary filename
    // rather than use drupal_tempnam(), because OpenSTV only accepts files with a
    // .blt extension.
    $destination = 'temporary://election-count-' . (int) $electionPost->post_id . '.blt';

    $stream = fopen($destination, 'w');
    if (!$stream) {
      return FALSE;
    }

    $default_options = [
      'include ballot ids' => FALSE,
    ];
    $options = array_merge($default_options, $options);

    // Load all candidates, including unpublished ones, unsorted.
    $candidates = $electionPost->getCandidates(NULL, FALSE);

    // Get rid of 'rejected' candidates.
    foreach ($candidates as $key => $candidate) {
      if ($candidate->candidate_status->value == 'rejected') {
        unset($candidates[$key]);
      }
    }

    // Map the candidates to integer keys.
    $candidate_map = [];
    $i = 1;
    $candidate_lines = '';
    $total_candidates = (int) count($candidates);
    $withdrawn = [];
    foreach ($candidates as $candidate) {
      $candidate_map[$candidate->candidate_id] = $i;
      if ($candidate->cstatus == 'withdrawn') {
        $withdrawn[] = $i;
      }
      $name = $candidate->label();
      $name = addcslashes($name, '"');
      $candidate_lines .= '"' . $name . '"';
      $candidate_lines .= ' # ' . t('Candidate keyed by @i', ['@i' => $i]) . "\n";
      $i++;
    }

    if (count($candidates) && $electionPost->include_reopen_nominations->value == 1) {
      $candidate_map['ron'] = $i;
      $total_candidates++;
      $candidate_lines .= '"' . t('RON (Re-Open Nominations)') . '"';
      $candidate_lines .= ' # ' . t('Candidate keyed by @i', array('@i' => $i)) . "\n";
    }

    $votes_fields = ['ballot_id', 'candidate_id', 'ron', 'rank'];

    // @todo how does this filter by the post?
    // @todo restrict to confirmed ballots only
    $votes_query = \Drupal::database()->select('election_ballot_vote', 'ev');
    $votes_query->join('election_ballot', 'eb', 'eb.id = ev.ballot_id');
    $votes_query->fields('ev', $votes_fields)
      ->fields('ev', ['ranking', 'abstention'])
      ->condition('ev.ranking', 0, '>')
      ->orderBy('ev.ballot_id')
      ->orderBy('ev.ranking');

    $votes = $votes_query->execute();

    $allow_equal = $electionPost->allow_equal_ranking->value == 1;
    $allow_abstention = $electionPost->allow_abstention->value == 1;

    $ballots = [];
    $multipliers = [];
    $ballot_errors = [];
    $last_rank = NULL;
    foreach ($votes as $vote) {

      $ballot_id = $vote->ballot_id->value;

      if (!isset($ballots[$ballot_id])) {
        $ballots[$ballot_id] = '';
        // @todo what?:
        $multipliers[$ballot_id] = $vote->value;
        if ($vote->abstention->value == 1) {
          if (!$allow_abstention) {
            $ballot_errors[$ballot_id] = t('Abstention found, but abstention is not allowed!');
          }
          continue;
        }
      } elseif (isset($last_rank) && $last_rank === $vote->ranking->value) {
        if (!$allow_equal) {
          $ballot_errors[$ballot_id] = t('Equal ranking found, but equal ranking is not allowed!');
        }
        $ballots[$ballot_id] .= '=';
      } else {
        $ballots[$ballot_id] .= ' ';
      }
      $candidate_id = $vote->ron->value ? 'ron' : $vote->candidate_id->value;
      $ballots[$ballot_id] .= $candidate_map[$candidate_id];
      $last_rank = $vote->ranking->value;
    }

    $output = "################################################\n";

    $output .= '# ' . t(
      'Ballot file generated on !date',
      array('!date' => \Drupal::service('date.formatter')->format(\Drupal::time()->getRequestTime(), 'custom', 'Y-m-d H:i:s'))
    ) . " #\n";

    $output .= "################################################\n";

    $output .= '# ' . t(
      '!candidates standing for !vacancies:',
      array(
        '!candidates' => format_plural($total_candidates, 'One candidate is', '@count candidates are'),
        '!vacancies' => format_plural($electionPost->vacancies->value, 'one vacancy', '@count vacancies'),
      )
    ) . "\n";

    $output .= $total_candidates . ' ' . $electionPost->vacancies->value . "\n";

    if (!empty($withdrawn)) {
      $output .= '# ' . format_plural(
        count($withdrawn),
        "One candidate has withdrawn:",
        "@count candidates have withdrawn:"
      ) . "\n";
      $output .= '-' . implode(' -', $withdrawn) . "\n";
    }

    $output .= "# " . t('Votes are listed below. Each line is in the format:') . "\n#    ";
    if ($options['include ballot ids']) {
      $output .= '(BALLOT_ID) ';
    }
    $output .= t('MULTIPLIER [CANDIDATE CANDIDATE ...] 0');
    $output .= "\n# " . t('where candidates are represented by keys in order of preference.');
    $output .= "\n# " . t('Candidate keys are each separated by a space, or by = for equal rankings.') . "\n";

    // Flush current data to the stream.
    fwrite($stream, $output);
    unset($output);

    // Generate the ballot lines.
    foreach ($ballots as $ballot_id => $ballot) {
      $line = '';
      if ($options['include ballot ids']) {
        $line .= '(' . $ballot_id . ') ';
      }
      // Each line begins with a multiplier and ends with 0.
      $multiplier = $multipliers[$ballot_id];
      if (empty($ballot)) {
        // This is an abstention.
        $line .= $multiplier . ' 0';
      } else {
        $line .= $multiplier . ' ' . $ballot . ' 0';
      }
      // Add any error messages for this ballot in a comment at the end.
      if (isset($ballot_errors[$ballot_id])) {
        $line .= ' # ' . $ballot_errors[$ballot_id];
      }
      $line .= "\n";
      fwrite($stream, $line);
    }

    $output = '0 # ' . t('This marks the end of votes.') . "\n";

    $output .= $candidate_lines;

    $output .= '"' . addcslashes($electionPost->label(), '"') . "\"\n";
    $output .= '"' . addcslashes($electionPost->getElection()->label(), '"') . "\"\n";

    fwrite($stream, $output);

    fclose($stream);

    $export_filename = $destination;
    return \Drupal::service('file_system')->realpath($export_filename);
  }
}
