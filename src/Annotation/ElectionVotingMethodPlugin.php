<?php

namespace Drupal\election\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Election voting method plugin item annotation object.
 *
 * @see \Drupal\election\Plugin\ElectionVotingMethodPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class ElectionVotingMethodPlugin extends Plugin {


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
