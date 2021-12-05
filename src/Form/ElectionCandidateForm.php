<?php

namespace Drupal\election\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Election candidate edit forms.
 *
 * @ingroup election
 */
class ElectionCandidateForm extends ContentEntityForm {

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    $instance = parent::create($container);
    $instance->account = $container->get('current_user');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $election_post = NULL) {
    /* @var \Drupal\election\Entity\ElectionCandidate $entity */
    $form = parent::buildForm($form, $form_state);

    $election_candidate = $this->entity;
    $election_candidate_type = $election_candidate->getElectionCandidateType();
    if ($election_candidate) {
      $form['#title'] = $election_candidate_type->getActionNaming();
    }

    if (!is_null($election_post)) {
      $election = $election_post->getElection();
      $form['#title'] = $election_candidate_type->getActionNaming($election_post);

      if (isset($form['election_post'])) {
        if ($election->access('update')) {
          $form['election_post']['widget'][0]['target_id']['#default_value'] = $election_post;
          $form['election_post']['#disabled'] = TRUE;
        }
      }
    }

    // Determine whether to show expression of interest form display mode or candidate form display mode
    // @todo

    if (!$this->entity->isNew()) {
      $form['new_revision'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Create new revision'),
        '#default_value' => FALSE,
        '#weight' => 10,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('new_revision') && $form_state->getValue('new_revision') != FALSE) {
      $entity->setNewRevision();

      // If a new revision is created, save the current user as revision author.
      $entity->setRevisionCreationTime($this->time->getRequestTime());
      $entity->setRevisionUserId($this->account->id());
    } else {
      $entity->setNewRevision(FALSE);
    }

    $status = parent::save($form, $form_state);

    // Set published by default (or not)
    if ($status == SAVED_NEW) {
      $entity->set('status', $entity->getPost()->publish_candidates_automatically->value);
      $entity->save();
    }

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Election candidate.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Election candidate.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.election_candidate.canonical', ['election_candidate' => $entity->id()]);
  }
}
