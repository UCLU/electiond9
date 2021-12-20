<?php

namespace Drupal\complex_conditions\Event;

use Drupal\complex_conditions\EventBase;

/**
 * Defines the event for filtering the available conditions.
 */
class FilterConditionsEvent extends EventBase {

  /**
   * The condition definitions.
   *
   * @var array
   */
  protected $definitions;

  /**
   * The condition types..
   *
   * @var string
   */
  protected $conditionTypes;

  /**
   * Constructs a new FilterConditionsEvent object.
   *
   * @param array $definitions
   *   The condition definitions.
   * @param array $field_types
   */
  public function __construct(array $definitions, array $condition_types) {
    $this->definitions = $definitions;
    $this->conditionTypes = $condition_types;
  }

  /**
   * Gets the condition definitions.
   *
   * @return array
   *   The condition definitions.
   */
  public function getDefinitions() {
    return $this->definitions;
  }

  /**
   * Sets the condition definitions.
   *
   * @param array $definitions
   *   The condition definitions.
   *
   * @return $this
   */
  public function setDefinitions(array $definitions) {
    $this->definitions = $definitions;
    return $this;
  }

  /**
   * Gets the parent entity type ID.
   *
   * @return string
   *   The parent entity type ID.
   */
  public function getConditionTypes() {
    return $this->conditionTypes;
  }
}
