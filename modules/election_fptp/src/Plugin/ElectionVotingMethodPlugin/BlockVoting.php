<?php

namespace Drupal\election_fptp\Plugin\ElectionVotingMethodPlugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\election\Entity\ElectionPost;
use Drupal\election\Plugin\ElectionVotingMethodPluginBase as PluginElectionVotingMethodPluginBase;

/**
 * Single transferable vote.
 *
 * @ElectionVotingMethodPlugin(
 *   id = "block",
 *   label = @Translation("Block voting (multiple non-transferable)"),
 * )
 */
class BlockVoting extends FirstPastThePost {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    parent::buildConfigurationForm($form, $form_state);

    $form['maximum_candidates'] = [
      '#type' => 'number',
      '#title' => 'Maximum candidates that can be selected',
      '#min' => 1,
      '#default_value' => $this->configuration['maximum_candidates'],
    ];

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
    $this->configuration['maximum_candidates'] = $form_state->getValue('maximum_candidates');
  }
}
