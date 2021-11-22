<?php

namespace Drupal\election\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Election post condition plugin item annotation object.
 *
 * @see \Drupal\election\Plugin\ElectionPostConditionPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class ElectionPostConditionPlugin extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
