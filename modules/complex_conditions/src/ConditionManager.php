<?php

namespace Drupal\complex_conditions;

use Drupal\complex_conditions\Event\FilterConditionsEvent;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\complex_conditions\Event\ConditionsEvents;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\CategorizingPluginManagerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Manages discovery and instantiation of condition plugins.
 *
 * @see plugin_api
 */
class ConditionManager extends DefaultPluginManager implements ConditionManagerInterface {

  use CategorizingPluginManagerTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new ConditionManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher) {
    parent::__construct(
      'Plugin/ComplexConditions/Condition',
      $namespaces,
      $module_handler,
      'Drupal\complex_conditions\Plugin\ComplexConditions\Condition\ConditionInterface',
      'Drupal\complex_conditions\Annotation\ComplexCondition'
    );

    $this->alterInfo('complex_conditions_info');
    $this->setCacheBackend($cache_backend, 'complex_conditions_plugins');
    $this->entityTypeManager = $entity_type_manager;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    foreach (['id', 'label'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new PluginException(sprintf('The condition "%s" must define the "%s" property.', $plugin_id, $required_property));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFilteredDefinitions(array $condition_types = []) {
    $definitions = $this->getDefinitions();
    foreach ($definitions as $plugin_id => $definition) {
      // Filter by entity type.
      if (count($condition_types) > 0 && isset($definition['condition_types']) && count(array_intersect($definition['condition_types'], $condition_types)) == 0) {
        unset($definitions[$plugin_id]);
        continue;
      }
    }

    // Allow modules to filter the condition list.
    $event = new FilterConditionsEvent($definitions, $condition_types);
    $this->eventDispatcher->dispatch(ConditionsEvents::FILTER_CONDITIONS, $event);

    // Sort by weigh and display label.
    uasort($definitions, function ($a, $b) {
      if ($a['weight'] == $b['weight']) {
        return strnatcasecmp($a['display_label'], $b['display_label']);
      }
      return ($a['weight'] < $b['weight']) ? -1 : 1;
    });

    return $definitions;
  }
}
