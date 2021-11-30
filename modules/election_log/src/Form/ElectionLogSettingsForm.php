<?php

namespace Drupal\election_log\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ElectionSettingsForm.
 *
 * @ingroup election
 */
class ElectionLogSettingsForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'election_log.settings';

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'election_log_settings';
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
      ->set('log_access', array_filter($form_state->getValue('log_access')))
      ->set('log_ballots', array_filter($form_state->getValue('log_ballots')))
      ->set('log_actions', array_filter($form_state->getValue('log_actions')))
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

    $form['log_forms'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log all election candidate and post form submissions (create nomination, edit)'),
      '#default_value' => $config->get('log_actions'),
    ];

    $form['log_ballots'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log all submissions of ballot forms (not including any details of the votes themselves)'),
      '#default_value' => $config->get('log_ballots'),
    ];

    $form['log_access'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log all election access'),
      '#default_value' => $config->get('log_access'),
    ];

    return parent::buildForm($form, $form_state);
  }
}
