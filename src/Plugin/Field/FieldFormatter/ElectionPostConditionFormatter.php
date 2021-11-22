<?php

namespace Drupal\election\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

 /**
   * Plugin implementation of the 'election_post_condition' formatter.
   *
   * @FieldFormatter (
   *   id = "election_post_condition_formatter",
   *   label = @Translation("Election post condition individual formatter"),
   *   field_types = {
   *     "election_post_condition"
   *   }
   * )
   */
  class ElectionPostConditionFormatter extends FormatterBase {
    /**
 * {@inheritdoc}
 */
public function viewElements(FieldItemListInterface $items, $langcode = NULL) {
  $elements = array();

  foreach ($items as $delta => $item) {
    $markup = '';
    
    if($item->condition_type == 'user_role') {
      $role = \Drupal::entityTypeManager()->getStorage('user_role')->load($item->user_role);
      $markup = 'Must have "' . $role->get('label')->value.'" role';
    } elseif($item->condition_type == 'group') {
      $group = \Drupal::entityTypeManager()->getStorage('group')->load($item->group);
      $markup = 'Must be a member of "' . $group->get('label')->value.'"';
      if(!empty($item->group_role)) {
        $group_role = \Drupal::entityTypeManager()->getStorage('group_role')->load($item->group_role);
        $markup = ' with the group role of "' . $group_role->get('label')->value.'"';
      }
    } elseif($item->condition_type == 'user_field_value') {
      $markup = 'Must have "'.$item->user_field_name.'" which is '.$item->user_field_operator.' '.$item->user_field_value;
    }

    $elements[$delta] = array(
      '#type' => 'markup',
      '#markup' => $markup,
    );
  }

  return $elements;
}
  }