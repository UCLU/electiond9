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
class ElectionCountForm extends ElectionPostCountForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'election_count_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ElectionInterface $election = NULL) {
    $form = parent::buildForm();
    $form_state->set('election_id', $election->id());
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function setOperations(&$batch_builder, $form_state) {
    $election = Election::load($form_state->get('election_id'));
    $data = $election->getPosts();

    // Chunk the array
    $chunk_size = 10;
    $chunks = array_chunk($data, $chunk_size, TRUE);
    foreach ($chunks as $chunk) {
      $batch_builder->addOperation([$this, 'processBatch'], [$chunk]);
    }
  }


  public function getCountFormTitle() {
    return $this->t('Run count');
  }
}
