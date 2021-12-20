<?php

namespace Drupal\complex_conditions\Event;

/**
 * Defines events for the base Commerce module.
 *
 * Note that submodules have their own defined events.
 */
final class ConditionsEvents {

  /**
   * Name of the event fired when filtering available conditions.
   *
   * @Event
   *
   * @see \Drupal\complex_conditions\Event\FilterConditionsEvent
   */
  const FILTER_CONDITIONS = 'complex_conditions.filter_conditions';

  /**
   * Name of the event fired when altering the referenceable plugin types.
   *
   * @Event
   *
   * @see \Drupal\complex_conditions\Event\ReferenceablePluginTypesEvent.php
   */
  const REFERENCEABLE_PLUGIN_TYPES = 'complex_conditions.referenceable_plugin_types';
}
