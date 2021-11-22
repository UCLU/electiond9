<?php

namespace Drupal\election\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for Election post result handler plugin plugins.
 */
abstract class ElectionPostResultHandlerPluginBase extends PluginBase implements ElectionPostResultHandlerPluginInterface {

  function onCount() {
  }

  function onSupersededByRevision() {
  }
}
