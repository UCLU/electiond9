<?php

namespace Drupal\conditions_plugin_reference\Event;

use Drupal\conditions_plugin_reference\ConditionRequirement;
use Drupal\conditions_plugin_reference\EventBase;

/**
 * Defines the event for filtering the available conditions.
 */
class ConditionRequirementCheckedEvent extends EventBase {

  /**
   * The condition definitions.
   *
   * @var ConditionRequirement
   */
  protected $requirement;

  /**
   * Constructs a new FilterConditionsEvent object.
   *
   * @param array $definitions
   *   The condition definitions.
   * @param array $field_types
   */
  public function __construct(ConditionRequirement $requirement) {
    $this->requirement = $requirement;
  }

  /**
   * Gets the condition definitions.
   *
   * @return array
   *   The condition definitions.
   */
  public function getRequirement() {
    return $this->requirement;
  }

  /**
   * Sets the condition definitions.
   *
   * @param array $definitions
   *   The condition definitions.
   *
   * @return $this
   */
  public function setRequirement(ConditionRequirement $requirement) {
    $this->requirement = $requirement;
    return $this;
  }
}
