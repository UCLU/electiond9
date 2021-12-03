<?php

namespace Drupal\election_conditions\Plugin\Field\FieldWidget;

use Drupal\conditions_plugin_reference\Plugin\Field\FieldWidget\ConditionsTable as FieldWidgetConditionsTable;

/**
 * @FieldWidget(
 *   id = "conditions_plugin_reference_conditions_table_election",
 *   label = @Translation("Election Conditions Table"),
 *   field_types = {
 *     "conditions_plugin_item:election_condition"
 *   },
 *   multiple_values = TRUE
 * )
 */
class ElectionConditionsTable extends FieldWidgetConditionsTable {
  const CONDITION_TYPES = ['election'];
}
