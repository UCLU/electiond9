<?php

declare(strict_types=1);

namespace Drupal\conditions_plugin_reference\Plugin\Field\FieldWidget;

use Drupal\conditions_plugin_reference\Plugin\Field\FieldWidget\ConditionsWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'conditions_plugin_reference' widget.
 *
 * https://git.drupalcode.org/project/commerce_conditions_plus/-/blob/1.0.x/src/Plugin/Field/FieldWidget/ConditionsTable.php
 *
 * @FieldWidget(
 *   id = "conditions_plugin_reference_conditions_table",
 *   label = @Translation("Conditions Table"),
 *   field_types = {
 *     "conditions_plugin_item:condition_plugin_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class ConditionsTable extends ConditionsWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['form']['#type'] = 'conditions_plugin_reference_table';
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // AND and OR operators have no configuration, so when they are submitted
    // the form has no `configuration` array. We add it in so that the parent
    // massageFormValues passes.
    // @todo Upstream should support configurationless conditions (albeit rare.)
    foreach ($values['form'] as $key => $value) {
      if (!isset($value['plugin'])) {
        continue;
      }
      if (empty($value['configuration'])) {
        $values['form'][$key]['configuration'] = [];
      }
    }
    return parent::massageFormValues($values, $form, $form_state);
  }
}
