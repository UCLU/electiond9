<?php

namespace Drupal\election\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\election\Entity\ElectionCandidate;

/**
 * Provides a form for deleting Election candidate entities.
 *
 * @ingroup election
 */
class ElectionCandidateDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $election_candidate = $this->getEntity();

    return $form;
  }
}
