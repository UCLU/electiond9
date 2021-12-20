<?php

namespace Drupal\election_login_links\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\csv_import_export\Form\BatchDownloadCSVForm;
use Drupal\election\Entity\Election;
use Drupal\election\Entity\ElectionInterface;
use Drupal\election_login_links\Entity\ElectionLoginHash;

/**
 * Class GenerateLinksForm.
 */
class GenerateLinksForm extends BatchDownloadCSVForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'generate_links_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ElectionInterface $election = NULL) {

    $form = parent::buildForm($form, $form_state);

    $form_state->set('election_id', $election->id());

    // How many links currently available at what expiry times - markup
    $hashes = \Drupal::entityQuery('election_login_hash')
      ->condition('election_id', $election->id())
      ->condition('used', 0)
      ->condition('expiry', strtotime('now'), '>')
      ->execute();
    $count = count($hashes);

    $form['markup'] = [
      '#type' => 'markup',
      '#prefix' => '<p>',
      '#markup' => $this->t('Current one-time login links generated and available for this election: @count', ['@count' => $count]),
      '#suffix' => '</p>',
    ];

    $form['expiry'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Expiry'),
      '#default_value' => DrupalDateTime::createFromTimestamp(strtotime('+1 month')),
    ];

    // Actions
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Download list of one-time login links',
      '#button_type' => 'primary',
      '#submit' => array(
        '::getEligible',
      ),
    ];

    if (count($hashes) > 0) {
      $form['actions']['submit_delete'] = [
        '#type' => 'submit',
        '#value' => 'Cancel all existing login links',
        '#button_type' => 'danger',
        '#submit' => array(
          '::deleteAll',
        ),
      ];
    }

    return $form;
  }

  public function getEligible(array &$form, FormStateInterface $form_state) {
    $form_state->set('type', 'eligible');
    parent::submitForm($form, $form_state);
  }

  public function deleteAll(array &$form, FormStateInterface $form_state) {
    $form_state->set('type', 'delete');
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function setOperations(&$batch_builder, array &$form, FormStateInterface $form_state) {
    if ($form_state->get('type') == 'delete') {
      $data = \Drupal::entityQuery('election_login_hash')
        ->condition('election_id', $form_state->get('election_id'))
        ->condition('used', 0)
        ->execute();

      $function = 'processDeletions';
      $chunk_size = 200;
    } else {
      $data = \Drupal::entityQuery('user')
        ->condition('status', 1)
        ->execute();

      $function = 'processBatch';
      $chunk_size = 50;
    }

    // Chunk the array
    $chunks = array_chunk($data, $chunk_size, TRUE);
    foreach ($chunks as $chunk) {
      $batch_builder->addOperation([$this, $function], [$chunk]);
    }
  }

  public function processDeletions($rows, array &$context) {
    foreach ($rows as $row) {
      $row->delete();
    }
  }

  public function processBatch($users, array &$context) {
    $election = Election::load($form_state->get('election_id'));
    foreach ($users as $user) {
      $line = [
        'User ID' => $user->id(),
        'User e-mail' => $user->getEmail(),
        'One-time login link' => $this->generateLink($user, $election, $form_state->getValue['expiry']),
      ];
      $this->addLine($line, $context);
    }
  }

  /**
   * @param AccountInterface $account
   * @param ElectionInterface $election
   * @param int $timestamp
   *
   * @return [type]
   */
  public function generateLink(AccountInterface $account, ElectionInterface $election, int $timestamp) {
    $hash = ElectionLoginHash::create([
      'expiry' => $timestamp,
      'used' => 0,
    ]);
    $hash->setOwner($account);
    $hash->setElection($election);
    $hash->setHash();
    $hash->save();

    return $hash->getLink();
  }
}
