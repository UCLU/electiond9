<?php

namespace Drupal\conditions_plugin_reference;

use Drupal\Component\Plugin\CategorizingPluginManagerInterface;

/**
 * Defines the interface for conditions_plugin_reference plugin managers.
 */
interface ConditionManagerInterface extends CategorizingPluginManagerInterface {

  /**
   * Gets the filtered plugin definitions.
   *
   * @param array $plugin_types
   *
   * @return array
   *   The filtered plugin definitions.
   */
  public function getFilteredDefinitions(array $condition_types = []);
}
