<?php

namespace Drupal\election\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Election voting method plugin plugin manager.
 */
class ElectionVotingMethodPluginManager extends DefaultPluginManager {


  /**
   * Constructs a new ElectionVotingMethodPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/ElectionVotingMethod', $namespaces, $module_handler, 'Drupal\election\Plugin\ElectionVotingMethodInterface', 'Drupal\election\Annotation\ElectionVotingMethod');

    $this->alterInfo('election_election_voting_method_info');
    $this->setCacheBackend($cache_backend, 'election_election_voting_method_plugins');
  }
}
