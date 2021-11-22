<?php

namespace Drupal\election\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\election\Entity\ElectionPost;
use Drupal\election\Entity\ElectionPostType;

/**
 * Class ElectionTypeForm.
 */
class ElectionTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $election_type = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $election_type->label(),
      '#description' => $this->t("Label for the Election type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $election_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\election\Entity\ElectionType::load',
      ],
      '#disabled' => !$election_type->isNew(),
    ];

    $election_post_types = ElectionPostType::loadMultiple();
    $options = [];
    foreach ($election_post_types as $post_type) {
      $options[$post_type->id()] = $post_type->label();
    }
    $form['allowed_post_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed post types for this election type'),
      '#description' => $this->t(
        'Select none to allow all. <a href="@url" target="_blank">Manage post types here</a>.',
        [
          '@url' => Url::fromRoute('entity.election_post_type.collection')->toString(),
        ]
      ),
      '#default_value' => $election_type->get('allowed_post_types') ?? [],
      '#options' => $options,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $election_type = $this->entity;

    $status = $election_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Election type.', [
          '%label' => $election_type->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Election type.', [
          '%label' => $election_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($election_type->toUrl('collection'));
  }
}
