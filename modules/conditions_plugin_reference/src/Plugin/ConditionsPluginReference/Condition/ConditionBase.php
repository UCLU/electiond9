<?php

namespace Drupal\conditions_plugin_reference\Plugin\ConditionsPluginReference\Condition;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the base class for conditions.
 */
abstract class ConditionBase extends PluginBase implements ConditionInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
  }

  public function requiredParameters(): array {
    return [];
  }

  public function assertParameters(array $parameters) {
    $contained = !array_diff(array_keys($this->requiredParameters()), $parameters);
    assert($contained);
  }

  /**
   * Return true or false if condition is passed.
   *
   * @param string $phase
   *
   * @return boolean
   */
  public function evaluate(EntityInterface $entity, $parameters = []) {
    $this->assertParameters($parameters);
  }

  public function getReasons(EntityInterface $entity, $parameters = []) {
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration = [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayLabel() {
    return $this->pluginDefinition['display_label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getConditionSummary() {
    // including config
  }

  /**
   * Gets the comparison operators.
   *
   * @return array
   *   The comparison operators.
   */
  protected function getComparisonOperators() {
    return [
      '>' => $this->t('Greater than'),
      '>=' => $this->t('Greater than or equal to'),
      '<=' => $this->t('Less than or equal to'),
      '<' => $this->t('Less than'),
      '==' => $this->t('Equals'),
    ];
  }
}
