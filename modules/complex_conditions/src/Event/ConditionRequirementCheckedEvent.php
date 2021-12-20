<?php

namespace Drupal\complex_conditions\Event;

use Drupal\complex_conditions\ConditionRequirement;
use Drupal\complex_conditions\EventBase;

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
