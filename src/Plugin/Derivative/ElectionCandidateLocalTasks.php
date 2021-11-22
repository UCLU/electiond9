<?php

namespace Drupal\election\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Defines dynamic local tasks.
 */
class ElectionCandidateLocalTasks extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }
}
