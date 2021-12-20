<?php

namespace Drupal\election_conditions\Plugin\Field\FieldWidget;

use Drupal\complex_conditions\Plugin\Field\FieldWidget\ConditionsTable as FieldWidgetConditionsTable;

/**
 * @FieldWidget(
 *   id = "complex_conditions_conditions_table_election",
 *   label = @Translation("Election Conditions Table"),
 *   field_types = {
 *     "conditions_plugin_item:complex_conditions"
 *   },
 *   multiple_values = TRUE
 * )
 */
class ElectionConditionsTable extends FieldWidgetConditionsTable {
  const CONDITION_TYPES = ['election'];
}
