<?php

namespace Drupal\election\Plugin\views\field;

use DateTime;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\election\Entity\Election;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupRole;
use Drupal\user\Entity\User;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\field\NumericField;
use Drupal\views\ResultRow;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Get whether the user has a record for a grouip
 *
 * @ViewsField("eligibility_for_post")
 */
class EligibilityForPost extends FieldPluginBase {
  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['format'] = ['default' => 'simple'];
    $options['phases_to_show'] = ['default' => []];
    $options['link_to_action'] = ['default' => TRUE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Format for string'),
      '#options' => [
        'simple' => 'Simple',
        'full' => 'Full explanation',
        'links_only' => 'No explanation (just action link or empty)',
      ],
      '#default_value' => $this->options['format'],
    ];

    $form['link_to_action'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Link to actions'),
      '#description' => $this->t('e.g. if eligible to nominate or vote, link to forms.'),
      '#default_value' => $this->options['link_to_action'],
    ];

    $phases = [];
    foreach (Election::ELECTION_PHASES as $phase) {
      $phases[$phase] = Election::getPhaseName($phase);
    }
    $form['phases_to_show'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Election phases to include'),
      '#description' => $this->t('Selecting none shows all.'),
      '#options' => $phases,
      '#default_value' => $this->options['phases_to_show'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $election_post = $values->_entity;

    $phases = [];
    foreach ($this->options['phases_to_show'] as $key => $show) {
      if ($show) {
        $phases[] = $show;
      }
    }

    $formatted = $election_post->getUserEligibilityFormatted(\Drupal::currentUser(), $phases, ', ', $this->options['format'], $this->options['link_to_action']);

    return new FormattableMarkup($formatted, []);
  }
}
