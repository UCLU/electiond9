<?php

namespace Drupal\election_fptp\Plugin\ElectionVotingMethodPlugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\election\Entity\ElectionPost;
use Drupal\election\Plugin\ElectionVotingMethodPluginBase as PluginElectionVotingMethodPluginBase;

/**
 * Single transferable vote.
 *
 * @ElectionVotingMethodPlugin(
 *   id = "fptp",
 *   label = @Translation("First past the post / block voting"),
 * )
 */
class FirstPastThePost extends PluginElectionVotingMethodPluginBase {

  /**
   * {@inheritdoc}
   */
  public function countPosition(ElectionPost $electionPost, array $options = []) {
    $result = [];

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    parent::buildConfigurationForm($form, $form_state);

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
