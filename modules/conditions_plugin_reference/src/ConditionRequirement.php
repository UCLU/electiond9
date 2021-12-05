<?php

namespace Drupal\conditions_plugin_reference;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Represents a requirement for a condition.
 */
final class ConditionRequirement {

  /**
   * Requirement id.
   *
   * @var string
   */
  protected $id;

  /**
   * Requirement label.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  protected $label;

  /**
   * Requirement description.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  protected $description;

  /**
   * Whether the requirement has been passed or not.
   *
   * @var bool
   */
  protected $pass;

  /**
   * The ID of the condition group the requirement is part of, if any.
   *
   * @var string
   */
  protected $group;

  /**
   * Constructs a new Requirement object.
   *
   * @param array $definition
   *   The definition.
   */
  public function __construct(array $definition) {
    foreach (['id', 'label', 'pass'] as $required_property) {
      if (!isset($definition[$required_property])) {
        throw new \InvalidArgumentException(sprintf('Missing required property %s.', $required_property));
      }
    }

    $this->id = !empty($definition['id']) ? $definition['id'] : NULL;
    $this->label = !empty($definition['label']) ? $definition['label'] : NULL;
    $this->pass = !empty($definition['pass']) ? $definition['pass'] : NULL;
    $this->description = !empty($definition['description']) ? $definition['description'] : NULL;
    $this->group = !empty($definition['group']) ? $definition['group'] : NULL;
  }

  public function id() {
    return $this->getId();
  }

  public function getId() {
    return $this->id;
  }

  public function setId(string $id) {
    $this->id = $id;
  }

  public function label() {
    return $this->getLabel();
  }

  public function getLabel() {
    return $this->label;
  }

  public function setLabel(TranslatableMarkup $label) {
    $this->label = $label;
  }

  public function labelAsString() {
    return $this->label->render();
  }

  public function getPass() {
    return $this->pass;
  }

  public function isPassed() {
    return $this->pass;
  }

  public function isFailed() {
    return !$this->pass;
  }

  public function setPass(bool $pass) {
    $this->pass = $pass;
  }

  public function getDescription() {
    return $this->description;
  }

  public function setDescription(TranslatableMarkup $description) {
    $this->description = $description;
  }

  public function getGroup() {
    return $this->group;
  }

  public function setGroup(string $group) {
    $this->group = $group;
  }

  public static function anyPassed(array $requirements) {
    $result = static::getPassed($requirements);
    return count($result) > 0;
  }

  public static function allPassed(array $requirements) {
    $result = static::getPassed($requirements);
    return count($result) === count($requirements);
  }

  public static function getPassed(array $requirements) {
    $return = [];
    foreach ($requirements as $requirement) {
      if ($requirement->pass) {
        $return[] = $requirement;
      }
    }
    return $return;
  }

  public static function anyFailed(array $requirements) {
    $result = static::getFailed($requirements);
    return count($result) > 0;
  }

  public static function allFailed(array $requirements) {
    $result = static::getFailed($requirements);
    return count($result) === count($requirements);
  }

  public static function getFailed(array $requirements) {
    $return = [];
    foreach ($requirements as $requirement) {
      if (!$requirement->pass) {
        $return[] = $requirement;
      }
    }
    return $return;
  }

  public function negate() {
    $negatedLabel = t('NOT ' . $this->labelAsString());
    $this->setLabel($negatedLabel);
    $this->setPass(!$this->isPassed());
  }
}
