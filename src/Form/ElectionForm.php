<?php

namespace Drupal\election\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\election\ElectionStatusesTrait;
use Drupal\election_conditions\ElectionConditionsTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Election edit forms.
 *
 * @ingroup election
 */
class ElectionForm extends ContentEntityForm {

  use ElectionStatusesTrait;
  use ElectionConditionsTrait;

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
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\election\Entity\Election $entity */
    $form = parent::buildForm($form, $form_state);

    $form['#attached']['library'][] = 'election_conditions/conditions';

    // Hide and show fields:
    static::addStatusesStatesToForm($form);
    static::addConditionStatesToForm($form);

    // Revisions handling:
    if (!$this->entity->isNew()) {
      $form['new_revision'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Create new revision'),
        '#default_value' => FALSE,
        '#weight' => 50,
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
        $this->messenger()->addMessage($this->t('Created the %label Election.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Election.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.election.canonical', ['election' => $entity->id()]);
  }
}
