<?php

namespace Drupal\conditions_plugin_reference\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the inline form plugin annotation object.
 *
 * Plugin namespace: Plugin\Conditions\InlineForm.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class ConditionsInlineForm extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The plugin label.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;
}
