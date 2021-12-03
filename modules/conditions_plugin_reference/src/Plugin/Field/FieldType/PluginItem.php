<?php

namespace Drupal\conditions_plugin_reference\Plugin\Field\FieldType;

use Drupal\conditions_plugin_reference\Plugin\Field\FieldType\PluginItemInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;

/**
 * Plugin implementation of the 'commerce_plugin_item' field type.
 *
 * @FieldType(
 *   id = "conditions_plugin_item",
 *   label = @Translation("Plugin"),
 *   description = @Translation("Stores configuration for a plugin."),
 *   category = @Translation("Conditions"),
 *   default_widget = "conditions_plugin_reference_conditions_table",
 *   default_formatter = "conditions_plugin_item_default",
 *   deriver = "\Drupal\conditions_plugin_reference\Plugin\Field\FieldType\PluginItemDeriver"
 * )
 */
class PluginItem extends FieldItemBase implements PluginItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['target_plugin_id'] = DataDefinition::create('string')
      ->setLabel(t('Plugin ID'));
    $properties['target_plugin_configuration'] = MapDataDefinition::create()
      ->setLabel(t('Plugin configuration'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'target_plugin_id' => [
          'description' => 'The plugin ID.',
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
        ],
        'target_plugin_configuration' => [
          'description' => 'The plugin configuration.',
          'type' => 'blob',
          'not null' => TRUE,
          'serialize' => TRUE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'target_plugin_id';
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return empty($this->target_plugin_id) || $this->target_plugin_id == '_none';
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetDefinition() {
    return $this->getPluginManager()->getDefinition($this->target_plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetInstance(array $contexts = []) {
    $plugin = $this->getPluginManager()->createInstance($this->target_plugin_id, $this->target_plugin_configuration);
    // Just because the plugin is context aware, we cannot guarantee the
    // plugin manager sets them. So we apply the context mapping as well.
    if (!empty($contexts)) {
      \Drupal::service('context.handler')->applyContextMapping($plugin, $contexts);
    }

    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    if (isset($values)) {
      $values += [
        'target_plugin_configuration' => [],
      ];
    }

    parent::setValue($values, $notify);
  }

  /**
   * Gets the plugin manager.
   *
   * @return \Drupal\Core\Executable\ExecutableManagerInterface|\Drupal\Component\Plugin\CategorizingPluginManagerInterface
   *   The plugin manager.
   */
  protected function getPluginManager() {
    print_r($this->getPluginDefinition());
    return \Drupal::service('plugin.manager.conditions_plugin_reference'); // . $this->getPluginDefinition()['plugin_type']);
  }
}
