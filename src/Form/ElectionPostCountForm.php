<?php

namespace Drupal\election\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\csv_import_export\Form\BatchForm;
use Drupal\election\Entity\Election;
use Drupal\election\Entity\ElectionInterface;
use Drupal\election\Entity\ElectionPostInterface;

/**
 * Class CountForm.
 */
class ElectionPostCountForm extends BatchForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'election_post_count_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ElectionInterface $election = NULL) {
    $form_state->set('election_id', $election->id());
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function setOperations(&$batch_builder, $form_state) {
    $data = [$this];

    // Chunk the array
    $chunk_size = 10;
    $chunks = array_chunk($data, $chunk_size, TRUE);
    foreach ($chunks as $chunk) {
      $batch_builder->addOperation([$this, 'processBatch'], [$chunk]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function processOperation(array $election_posts, array &$context) {
    $updateCandidates = FALSE;
    foreach ($election_posts as $election_post) {
      $election_post->runCount($updateCandidates);
    }
  }

  public function getCountFormTitle() {
    return $this->t('Run count');
  }
}
