<?php

namespace Drupal\conditions_plugin_reference\Plugin\ConditionsPluginReference\Condition;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

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
    if (count($this->requiredParameters()) > 0) {
      $contained = !array_diff(array_keys($this->requiredParameters()), $parameters);
      assert($contained);
    }
  }

  /**
   * Return true or false if condition is passed.
   *
   * @param string $phase
   *
   * @return boolean
   */
  public function evaluate(EntityInterface $entity, AccountInterface $account, $parameters = []) {
    $this->assertParameters($parameters);
    $requirements = $this->evaluateRequirements($entity, $account, $parameters);
    $hasFailingRequirement = in_array(FALSE, array_column($requirements, 'pass'));
    return !$hasFailingRequirement;
  }

  /**
   * Returns an array with key being requirements and value beeing a boolean if they have been met.
   *
   * e.g. [
   *   'requirement_id' => [
   *     'title' => t('Example'),
   *     'pass' => FALSE,
   *   ],
   * ];
   *
   * @param EntityInterface $entity
   * @param AccountInterface $account
   * @param array $parameters
   *
   * @return array
   */
  public function evaluateRequirements(EntityInterface $entity, AccountInterface $account, $parameters = []) {
    $this->assertParameters($parameters);



    return [];
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
   * Get cache tags that should lead to re-calculating this condition result.
   *
   * E.g. by default it should be re-calculated only if the post or account data changes.
   *
   * @return array
   *   Array of cache tags.
   */
  public function getCacheTags(EntityInterface $entity, AccountInterface $account) {
    $tags = [];

    // Could e.g. get profile tags, or user

    return $tags;
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
