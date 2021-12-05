<?php

namespace Drupal\conditions_plugin_reference\Event;

/**
 * Defines events for the base Commerce module.
 *
 * Note that submodules have their own defined events.
 */
final class ConditionRequirementEvents {

  /**
   * Name of the event fired when filtering available conditions.
   *
   * @Event
   *
   * @see \Drupal\conditions_plugin_reference\Event\ConditionRequirementCheckedEvent
   */
  const CONDITION_REQUIREMENT_CHECKED = 'conditions_plugin_reference.requirement_checked';
}
