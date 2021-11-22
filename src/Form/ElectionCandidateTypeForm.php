<?php

namespace Drupal\election\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ElectionCandidateTypeForm.
 */
class ElectionCandidateTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $election_candidate_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $election_candidate_type->label(),
      '#description' => $this->t("Label for the Election candidate type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $election_candidate_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\election\Entity\ElectionCandidateType::load',
      ],
      '#disabled' => !$election_candidate_type->isNew(),
    ];

    $textFields = [
      'naming_candidate_singular' => [
        '#title' => $this->t('Term to use for candidates/options - singular'),
        '#description' => $this->t("e.g. candidate, option, applicant"),
        '#default_value' => $this->t('candidate'),
      ],
      'naming_candidate_plural' => [
        '#title' => $this->t('Term to use for candidates/options - plural'),
        '#description' => $this->t("e.g. candidates, options, applicants"),
        '#default_value' => $this->t('candidates'),
      ],
      'naming_candidate_action' => [
        '#title' => $this->t('Label for button to create candidate'),
        '#description' => $this->t("e.g. 'Add @candidate_type', 'Create', 'Nominate for @post_type'"),
        '#default_value' => $this->t('Add @candidate_type'),
      ],
    ];

    foreach ($textFields as $field => $data) {
      $form[$field] = array_merge($data, [
        '#type' => 'textfield',
        '#default_value' => $election_candidate_type->$field ?? $data['#default_value'],
      ]);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $election_candidate_type = $this->entity;
    $this->entity->set('naming_candidate_singular', $form_state->getvalue('naming_candidate_singular'));
    $this->entity->set('naming_candidate_plural', $form_state->getvalue('naming_candidate_plural'));
    $this->entity->set('naming_candidate_action', $form_state->getvalue('naming_candidate_action'));
    $status = $election_candidate_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label election candidate type.', [
          '%label' => $election_candidate_type->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label election candidate type.', [
          '%label' => $election_candidate_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($election_candidate_type->toUrl('collection'));
  }
}
