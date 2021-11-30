<?php

namespace Drupal\conditions_plugin_reference\Event;

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
   * @see \Drupal\conditions_plugin_reference\Event\FilterConditionsEvent
   */
  const FILTER_CONDITIONS = 'conditions_plugin_reference.filter_conditions';

  /**
   * Name of the event fired when altering the referenceable plugin types.
   *
   * @Event
   *
   * @see \Drupal\conditions_plugin_reference\Event\ReferenceablePluginTypesEvent.php
   */
  const REFERENCEABLE_PLUGIN_TYPES = 'conditions_plugin_reference.referenceable_plugin_types';
}
