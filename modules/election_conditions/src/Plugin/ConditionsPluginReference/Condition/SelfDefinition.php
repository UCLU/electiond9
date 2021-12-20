<?php

namespace Drupal\election_conditions\Plugin\ComplexConditions\Condition;

use Drupal\Core\Form\FormStateInterface;

use function PHPSTORM_META\map;

/**
 * Require an action before voting
 *
 * In this case, a text is shown and checkbox, but could e.g. be a self-definition question
 *
 * @ComplexConditions(
 *   id = "su_election_self_definition",
 *   condition_types = {
 *     "election",
 *   },
 *   label = @Translation("Self-definition required"),
 *   display_label = @Translation("Self-definition required"),
 *   category = @Translation("Actions"),
 *   weight = 0,
 * )
 */
class SelfDefinition extends ConfirmationRequired {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'field' => 'field_self_define_bme',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    unset($form['text']);
    unset($form['select_yes']);
    unset($form['select_no']);
    unset($form['submit']);

    $form['field'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t('Self-definition'),
      '#options' => [
        'bme' => 'BME',
      ],
      '#default_value' => $this->configuration['field'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);

    $this->configuration['field'] = $values['field'];

    $label = 'SELFDEFINELABEL';

    $this->configuration['text'] = 'You must self-define as ' . $label . ' in order to vote for this position.';
    $this->configuration['select_yes'] = t('I self define as @label', ['@label' => $label]);
    $this->configuration['select_no'] = t('I do not self define as @label', ['@label' => $label]);
    $this->configuration['submit'] = 'Submit';
  }
}
