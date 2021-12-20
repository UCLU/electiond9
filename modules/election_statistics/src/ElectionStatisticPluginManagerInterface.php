<?php

namespace Drupal\election_statistics;

use Drupal\Component\Plugin\CategorizingPluginManagerInterface;

/**
 * Defines the interface for complex_conditions plugin managers.
 */
interface ElectionStatisticPluginManagerInterface extends CategorizingPluginManagerInterface {

  /**
   * Gets the filtered plugin definitions.
   *
   * @return array
   *   The filtered plugin definitions.
   */
  public function getFilteredDefinitions();
}
