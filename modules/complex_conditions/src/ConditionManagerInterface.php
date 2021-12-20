<?php

namespace Drupal\complex_conditions;

use Drupal\Component\Plugin\CategorizingPluginManagerInterface;

/**
 * Defines the interface for complex_conditions plugin managers.
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
