<?php

namespace Drupal\conditions_plugin_reference\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines the condition plugin annotation object.
 *
 * Plugin namespace: Plugin\Condition.
 *
 * @Annotation
 */
class ConditionsPluginReference extends Plugin {

  use StringTranslationTrait;

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The condition label.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The condition display label.
   *
   * Shown in the condition UI when enabling/disabling a condition.
   * Defaults to the main label.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $display_label;

  /**
   * The condition category.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $category;

  /**
   * Condition types the plugin represents.
   *
   * @var array
   */
  public $condition_types;

  /**
   * The condition weight.
   *
   * Used when sorting the condition list in the UI.
   *
   * @var int
   */
  public $weight = 0;

  /**
   * Constructs a new CommerceCondition object.
   *
   * @param array $values
   *   The annotation values.
   */
  public function __construct(array $values) {
    if (empty($values['display_label'])) {
      $values['display_label'] = $values['label'];
    }
    if (empty($values['category'])) {
      $values['category'] = $this->t('Other');
    }
    parent::__construct($values);
  }
}
