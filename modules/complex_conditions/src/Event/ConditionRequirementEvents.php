<?php

namespace Drupal\complex_conditions\Event;

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
   * @see \Drupal\complex_conditions\Event\ConditionRequirementCheckedEvent
   */
  const CONDITION_REQUIREMENT_CHECKED = 'complex_conditions.requirement_checked';
}
