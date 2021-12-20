<?php

namespace Drupal\election_statistics;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\election\Entity\ElectionInterface;

interface ElectionStatisticInterface extends ConfigurableInterface, DependentPluginInterface, PluginFormInterface, PluginInspectionInterface {
  /**
   * @param ElectionInterface $election
   *
   * @return array
   *   Render array
   */
  public function generate(ElectionInterface $election);

  /**
   * @param ElectionInterface $election
   *
   * @return [type]
   */
  public function render(ElectionInterface $election);
}
