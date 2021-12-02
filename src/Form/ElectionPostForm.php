<?php

namespace Drupal\election\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\election_conditions\ElectionConditionsTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\election\Entity\Election;
use Drupal\election\ElectionStatusesTrait;

/**
 * Form controller for Election post edit forms.
 *
 * @ingroup election
 */
class ElectionPostForm extends ContentEntityForm {

  use ElectionStatusesTrait;

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
  public function buildForm(array $form, FormStateInterface $form_state, $election = NULL) {
    /* @var \Drupal\election\Entity\ElectionPost $entity */
    $form = parent::buildForm($form, $form_state);

    $form['#attached']['library'][] = 'election_conditions/conditions';

    // Hide and show fields:
    static::addStatusesStatesToForm($form);

    $election_post = $this->entity;
    if ($election_post) {
      $form['#title'] =  $election_post->getElectionPostType()->getActionNaming();
    }

    if (!is_null($election)) {

      if (isset($form['election'])) {
        if ($election->access('update')) {
          $form['election']['widget'][0]['target_id']['#default_value'] = $election;
          $form['election']['#disabled'] = TRUE;
        }
      }

      $phasesToShow = $election->getEnabledPhases();
      if (count($phasesToShow) != count(Election::ELECTION_PHASES)) {
        foreach (Election::ELECTION_PHASES as $key) {
          if (!isset($phasesToShow[$key])) {
            unset($form['conditions_' . $key]);
          }
        }
      }
    }

    // Hide and show voting conditions:
    foreach (Election::ELECTION_PHASES as $key) {
      $form['conditions_' . $key]['#states'] = [
        'visible' => [
          ':input[name="conditions_' . $key . '_same_as"]' => ['value' => $key],
        ],
      ];
    }

    if ($this->entity->isNew()) {
      unset($form['count_results_text']);
      unset($form['count_results_html']);
    } else {
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

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Election post.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Election post.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.election_post.canonical', [
      'election' => $entity->getElection()->id(),
      'election_post' => $entity->id()
    ]);
  }
}
