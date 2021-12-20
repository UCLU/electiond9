<?php

namespace Drupal\election_conditions\Plugin\ComplexConditions\Condition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\election_conditions\Plugin\ElectionConditionBase;

/**
 * Require an action before voting
 *
 * In this case, a text is shown and checkbox, but could e.g. be a self-definition question
 *
 * @ComplexConditions(
 *   id = "election_confirmation_required",
 *   condition_types = {
 *     "election",
 *   },
 *   label = @Translation("Confirmation required"),
 *   display_label = @Translation("Confirmation required"),
 *   category = @Translation("Actions"),
 *   weight = 0,
 * )
 */
class ConfirmationRequired extends ElectionConditionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'text' => [
        'value' => '',
        'format' => '',
      ],
      'select_yes' => 'Agree',
      'select_no' => 'Do not agree',
      'submit' => 'Submit',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['text'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Text to show'),
      '#default_value' => $this->configuration['text']['value'] ?? '',
      '#required' => TRUE,
    ];
    $form['select_yes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('"Yes" option'),
      '#default_value' => $this->configuration['select_yes'] ?? 'Yes',
      '#required' => TRUE,
    ];
    $form['select_no'] = [
      '#type' => 'textfield',
      '#title' => $this->t('"No" option'),
      '#default_value' => $this->configuration['select_no'] ?? 'No',
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Submit button text'),
      '#default_value' => $this->configuration['submit'] ?? 'Submit',
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);

    $this->configuration['text'] = $values['text'];
    $this->configuration['select_yes'] = $values['select_yes'];
    $this->configuration['select_no'] = $values['select_no'];
    $this->configuration['submit'] = $values['submit'];
  }
}
