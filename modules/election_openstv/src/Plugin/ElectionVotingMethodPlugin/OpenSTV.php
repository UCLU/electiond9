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
    return \Drupal::service('election_ballot_export.export_service')->exportPost($electionPost, $options);
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
