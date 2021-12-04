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
 * @ViewsField("election_post_actions")
 */
class PostActions extends FieldPluginBase {
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

    $options['phases_to_show'] = ['default' => []];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

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
        $phases[] = $key;
      }
    }

    $actions = $election_post->getActionLinks(\Drupal::currentUser(), $phases);

    $summary = [
      '#theme' => 'election_post_actions',
      '#actions' => $actions,
    ];

    return new FormattableMarkup(render($summary), []);
  }
}
