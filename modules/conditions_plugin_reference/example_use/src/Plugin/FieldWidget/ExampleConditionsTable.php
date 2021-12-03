<?php

namespace Drupal\YOUR_MODULE\Plugin\Field\FieldWidget;

use Drupal\conditions_plugin_reference\Plugin\Field\FieldWidget\ConditionsTable as FieldWidgetConditionsTable;

/**
 * @FieldWidget(
 *   id = "conditions_plugin_reference_conditions_table_example",
 *   label = @Translation("Example Conditions Table"),
 *   field_types = {
 *     "conditions_plugin_item:example_condition"
 *   },
 *   multiple_values = TRUE
 * )
 */
class ExampleConditionsTable extends FieldWidgetConditionsTable {
}
