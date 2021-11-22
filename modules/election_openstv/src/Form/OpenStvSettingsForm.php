<?php

namespace Drupal\election_openstv\Form;

use Drupal\Core\File\FileSystem;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class OpenStvSettingsForm.
 */
class OpenStvSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'election_openstv.openstvsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'open_stv_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('election_openstv.openstvsettings');
    $form['openstv_command'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OpenSTV command'),
      '#maxlength' => 255,
      '#size' => 255,
      '#default_value' => $config->get('openstv_command') ?? $this->getDefaultCommand(),
      '#required' => TRUE,
      '#description' => t('Configure the command used to run OpenSTV in your system.'),
      '#suffix' => t('<p>Example commands:</p>')
        . '<ul>'
        . '<li><code>openstv-run-election</code></li>'
        . '<li><code>python /usr/share/openstv/openstv/runElection.py</code></li>'
        . '<li><code>python sites/all/libraries/openstv/runElection.py</code></li>'
        . '<li><code>sites/all/libraries/openstv/runElection.py</code> (if the file is executable)</li>'
        . '</ul>',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('election_openstv.openstvsettings')
      ->set('openstv_command', $form_state->getValue('openstv_command'))
      ->save();
  }

  /**
   * Get python command for running OpenSTV.
   *
   * @return string
   *   Python executable command.
   */
  public function getDefaultCommand() {
    $config = $this->config('election_openstv.openstvsettings');
    $configured = $config->get('openstv_command');
    if (isset($configured)) {
      return $configured;
    }
    // Work out what the default command should be, if nothing is configured.
    $default = 'openstv-run-election';
    // Check whether OpenSTV exists in the libraries folder.

    $openstv_path = DRUPAL_ROOT . '/libraries/openstv';
    if ($openstv_path) {
      $run_election_path = \Drupal::service('file_system')->realpath($openstv_path . '/runElection.py');
      if (is_readable($run_election_path)) {
        $which_python = shell_exec('which python');
        $python = !empty($which_python) ? $which_python : 'python';
        $default = $python . ' ' . $run_election_path;
      }
    }
    return $default;
  }
}
