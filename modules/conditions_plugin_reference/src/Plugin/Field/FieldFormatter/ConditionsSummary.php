<?php

namespace Drupal\conditions_plugin_reference\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'commerce_plugin_item_default' formatter.
 *
 * @FieldFormatter(
 *   id = "conditions_summary",
 *   label = @Translation("Conditions summary"),
 *   field_types = {
 *     "conditions_plugin_item"
 *   }
 * )
 */
class ConditionsSummary extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $target_definition = $item->getTargetDefinition();
      if (!empty($target_definition['label'])) {
        $elements[$delta] = [
          '#markup' => $target_definition['label'],
        ];
      } else {
        $elements[$delta] = [
          '#markup' => $target_definition['id'],
        ];
      }
    }

    return $elements;
  }
}
