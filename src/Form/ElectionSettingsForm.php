<?php

namespace Drupal\election\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ElectionSettingsForm.
 *
 * @ingroup election
 */
class ElectionSettingsForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'election.settings';

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'election_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->configFactory->getEditable(static::SETTINGS)
      ->set('cache_eligibility_roles', array_filter($form_state->getValue('cache_eligibility_roles')))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Defines the settings form for Election entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['cache_eligibility_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('User roles for which the Elections module should cache eligibility'),
      '#description' => $this->t('Whether a user is eligible to vote or nominate for a post is cached once calculated, and only re-calculated whenever the user or the post is changed. This speeds up the views for the user, but can slow down some actions as the caching generally calculates eligibility for ALL users against a post, or ALL posts against a user. If you select none, no eligibility will be cached.'),
      '#default_value' => $config->get('cache_eligibility_roles'),
      '#options' => array_map('\Drupal\Component\Utility\Html::escape', user_role_names()),
    ];

    return parent::buildForm($form, $form_state);
  }
}
