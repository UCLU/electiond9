<?php

namespace Drupal\election_openstv\Plugin\ElectionVotingMethodPlugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\election\Entity\ElectionPost;
use Drupal\election\Plugin\ElectionVotingMethodPluginBase as PluginElectionVotingMethodPluginBase;

/**
 * Single transferable vote.
 *
 * @ElectionVotingMethodPlugin(
 *   id = "open_stv",
 *   label = @Translation("Single transferable vote (OpenSTV)"),
 * )
 */
class OpenSTV extends PluginElectionVotingMethodPluginBase {

  /**
   * The STV method.
   *
   * @var string
   */
  protected $method;

  /**
   * Set our instance value for STV method
   *
   * @param string $method
   *   The method of STV voting.
   */
  public function setMethod($method) {
    $this->method = $method;
  }

  /**
   * Answer our instance value for STV method.
   *
   * @return string
   *   The method of STV voting.
   */
  public function getMethod() {
    if (empty($this->method)) {
      return '';
    } else {
      return $this->method;
    }
  }

  /**
   *
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
      ->fields('eb', ['value', 'abstain'])
      ->condition('eb.value', 0, '>')
      ->orderBy('eb.ballot_id')
      ->orderBy('ev.rank');

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

  /**
   * {@inheritdoc}
   */
  public function countPosition(ElectionPost $electionPost, array $options = []) {
    // Get the absolute system path to the file.
    $export_filename = $this->exportPost($electionPost);

    $election = $electionPost->getElection();
    $method = $options['method'] ?? 'ERS97STV';

    // Build the OpenSTV command.
    $config = \Drupal::config('election_openstv.openstvsettings');
    $command = $config->get('openstv_command');
    $cmd = escapeshellcmd($command);
    $cmd .= ' -r ' . escapeshellarg('ResultsArray');
    $cmd .= ' ' . escapeshellarg($method);
    $cmd .= ' ' . escapeshellarg($export_filename);

    $descriptorspec = [
      0 => ['pipe', 'r'],
      1 => ['pipe', 'w'],
      2 => ['pipe', 'w'],
    ];

    // Run the OpenSTV command, capturing the result and any errors.
    $process = proc_open($cmd, $descriptorspec, $pipes);
    if ($process) {
      $result = stream_get_contents($pipes[1]);
      $error = stream_get_contents($pipes[2]);
      fclose($pipes[1]);
      fclose($pipes[2]);
      proc_close($process);
    } else {
      $error = t('Failed to run the OpenSTV command: @cmd', array('@cmd' => $cmd));
    }

    if (!empty($error)) {
      \Drupal::logger('election_openstv')->error($error);
    }

    // Delete the temporary export file.
    unlink($export_filename);

    if (empty($result)) {
      return FALSE;
    }

    $result = json_decode($result);

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    parent::buildConfigurationForm($form, $form_state);

    $form['method'] = [
      '#type' => 'select',
      '#title' => t('STV method'),
      '#default_value' => $this->getMethod(),
      '#options' => _election_openstv_get_methods(),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    if (empty($form_state->getValue('method'))) {
      $form_state->setErrorByName('method', t('Need to provide an STV method.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['method'] = $form_state->getValue('method');
  }
}
