<?php

namespace Drupal\election\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\Core\Url;
use Drupal\election\Annotation\ElectionVotingMethodPlugin;
use Drupal\election\Entity\ElectionCandidateType;

/**
 * Class ElectionPostTypeForm.
 */
class ElectionPostTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $election_post_type = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Administrative label'),
      '#maxlength' => 255,
      '#default_value' => $election_post_type->label(),
      '#description' => $this->t("Label for the Election post type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $election_post_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\election\Entity\ElectionPostType::load',
      ],
      '#disabled' => !$election_post_type->isNew(),
    ];

    $election_candidate_types = ElectionCandidateType::loadMultiple();
    $options = [];
    if (count($election_candidate_types) > 0) {
      foreach ($election_candidate_types as $candidate_type) {
        $options[$candidate_type->id()] = $candidate_type->label();
      }
    }

    $form['allowed_candidate_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed candidate types for this post type'),
      '#description' => $this->t(
        'Select none to allow all. <a href="@url" target="_blank">Manage candidate types here</a>.',
        [
          '@url' => Url::fromRoute('entity.election_candidate_type.collection')->toString(),
        ]
      ),
      '#default_value' => $election_post_type->get('allowed_candidate_types') ?? [],
      '#options' => $options,
    ];

    $textFields = [
      'naming_post_singular' => [
        '#title' => $this->t('Term to use for posts/position - singular'),
        '#description' => $this->t("e.g. position, question, post, role, vacancy"),
        '#default_value' => $this->t('position'),
      ],
      'naming_post_plural' => [
        '#title' => $this->t('Term to use for posts/position - plural'),
        '#description' => $this->t("e.g. positions, questions, posts, roles, vacancies"),
        '#default_value' => $this->t('positions'),
      ],
      'naming_post_action' => [
        '#title' => $this->t('Label for button to create post'),
        '#description' => $this->t("e.g. 'Create @post_type'"),
        '#default_value' => $this->t('Create @post_type'),
      ],
    ];

    foreach ($textFields as $field => $data) {
      $form[$field] = array_merge($data, [
        '#type' => 'textfield',
        '#default_value' => $election_post_type->$field ?? $data['#default_value'],
      ]);
    }

    return $form;
  }

  public function getVotingMethodPlugin(FormStateInterface $form_state) {
    $election_post_type = $this->entity;
    $pluginManager = \Drupal::service('plugin.manager.election_voting_method_plugin');
    if ($election_post_type->get('voting_method')) {
      return $pluginManager->createInstance($election_post_type->get('voting_method'));
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $plugin = $this->getVotingMethodPlugin($form_state);
    if ($plugin) {
      $plugin->validateConfigurationForm($form['voting_method_settings'], SubformState::createForSubform($form['voting_method_settings'], $form, $form_state));
    }
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    if (!isset($form['voting_method_settings']) || is_null($form['voting_method_settings'])) {
      $form['voting_method_settings'] = [];
    }

    $sub_form_state = SubformState::createForSubform($form['voting_method_settings'], $form, $form_state);
    $plugin = $this->getVotingMethodPlugin($form_state);
    if ($plugin) {
      $plugin->submitConfigurationForm($form, $sub_form_state);
    }

    $this->save($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $election_post_type = $this->entity;

    $this->entity->set('naming_post_singular', $form_state->getValue('naming_post_singular'));
    $this->entity->set('naming_post_plural', $form_state->getValue('naming_post_plural'));
    $this->entity->set('naming_post_action', $form_state->getValue('naming_post_action'));

    $this->entity->set('voting_method', $form_state->getValue('voting_method'));

    $status = $election_post_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Election post type.', [
          '%label' => $election_post_type->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Election post type.', [
          '%label' => $election_post_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($election_post_type->toUrl('collection'));
  }

  public function getVotingMethodForm(ElectionVotingMethodPlugin $voting_method) {
    if ($voting_method instanceof PluginWithFormsInterface) {
      $pluginManager = \Drupal::service('plugin.manager.election_voting_method_plugin');
      return $pluginManager->createInstance($voting_method, 'configure');
    }
    return $voting_method;
  }
}
