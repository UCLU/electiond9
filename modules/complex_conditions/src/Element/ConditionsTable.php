<?php

declare(strict_types=1);

namespace Drupal\complex_conditions\Element;

use Drupal\complex_conditions\Plugin\ComplexConditions\Condition\ConditionInterface;
use Drupal\complex_conditions\Plugin\ComplexConditions\InlineForm\PluginConfiguration;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * @FormElement("complex_conditions_table")
 *
 * Based on https://git.drupalcode.org/project/commerce_conditions_plus/-/blob/1.0.x/src/Element/ConditionsTable.php
 */
class ConditionsTable extends FormElement {

  use ConditionsElementTrait;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#tree' => TRUE,
      '#default_value' => [],
      '#title' => '',

      '#process' => [
        [$class, 'attachElementSubmit'],
        [$class, 'processConditions'],
        [$class, 'processAjaxForm'],
      ],
      '#element_validate' => [
        [$class, 'validateElementSubmit'],
      ],
      '#conditions_element_submit' => [
        [$class, 'submitConditions'],
      ],
      '#theme_wrappers' => ['container'],
    ];
  }

  /**
   * Processes the conditions form element.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   *
   * @throws \InvalidArgumentException
   *   Thrown for missing or malformed #parent_entity_type, #entity_types,
   *   #default_value properties.
   */
  public static function processConditions(array &$element, FormStateInterface $form_state, array &$complete_form) {
    if (!is_array($element['#default_value'])) {
      throw new \InvalidArgumentException('The conditions_table #default_value property must be an array.');
    }

    $condition_plugins_field_name = str_replace('[form]', '', $element['#name']);

    /** @var \Drupal\complex_conditions\ConditionManagerInterface $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.complex_conditions');
    $definitions = $plugin_manager->getFilteredDefinitions($element['#condition_types'] ?? []);
    $grouped_definitions = [];
    foreach ($definitions as $plugin_id => $definition) {
      $category = (string) $definition['category'];
      $grouped_definitions[$category][$plugin_id] = $definition['label'];
    }
    ksort($grouped_definitions);

    $ajax_wrapper_id = Html::getUniqueId('ajax-wrapper-conditions-table');
    $element['#prefix'] = '<div id="' . $ajax_wrapper_id . '">';
    $element['#suffix'] = '</div>';

    // $element['#type'] = 'item';

    $conditions = $form_state->get($condition_plugins_field_name);
    // On first load, make sure we have the default value respected and stored
    // in the form state for further AJAX operations.
    if ($conditions === NULL) {
      $conditions = $element['#default_value'];
      $form_state->set($condition_plugins_field_name, $conditions);
    }
    $element[$condition_plugins_field_name] = [
      '#type' => 'table',
      '#header' => [
        t('Condition'),
        t('Settings'),
        t('Negate'),
        t('Operations'),
        t('Weight'),
      ],
      '#tabledrag' => [
        [
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'plugin-parent',
          'subgroup' => 'plugin-parent',
          // @todo each condition needs its own unique UUID.
          'source' => 'plugin-id',
          'hidden' => FALSE,
        ],
        [
          'action' => 'depth',
          'relationship' => 'group',
          'group' => 'plugin-depth',
          'hidden' => FALSE,
        ],
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'plugin-weight',
        ],
      ],
      '#rows' => [],
      '#empty' => 'No conditions will be applied',
      // #input defaults to TRUE, which breaks file fields on the value form.
      // This table is used for visual grouping only, the element itself
      // doesn't have any values of its own that need processing.
      '#input' => FALSE,
    ];

    $inline_form_manager = \Drupal::service('plugin.manager.conditions_inline_form');

    $max_weight = count($conditions);
    $renderer = \Drupal::getContainer()->get('renderer');
    foreach ($conditions as $index => $condition) {
      $element[$condition_plugins_field_name][$index] = [];
      $condition_form = &$element[$condition_plugins_field_name][$index];

      // The tabledrag element is always added to the first cell in the row,
      // so we add an empty cell to guide it there, for better styling.
      $condition_form['#attributes']['class'][] = 'draggable';

      $inline_form = $inline_form_manager->createInstance('plugin_configuration', [
        'plugin_type' => 'complex_conditions',
        'plugin_id' => $condition['plugin'],
        'plugin_configuration' => $condition['configuration'],
        'enforce_unique_parents' => FALSE,
      ]);
      assert($inline_form instanceof PluginConfiguration);

      $indentation = [];
      if (isset($condition['configuration']['depth']) && $condition['configuration']['depth'] > 0) {
        $indentation = [
          '#theme' => 'indentation',
          '#size' => $condition['configuration']['depth'],
        ];
      }
      $condition_form['label'] = [
        '#prefix' => !empty($indentation) ? $renderer->renderPlain($indentation) : '',
        '#markup' => $definitions[$condition['plugin']]['display_label'],
      ];
      $condition_form['label']['plugin'] = [
        '#type' => 'hidden',
        '#value' => $condition['plugin'],
        '#parents' => array_merge($element['#parents'], [$index, 'plugin']),
        '#attributes' => [
          'class' => ['plugin-id'],
        ],
      ];
      $condition_form['label']['parent'] = [
        '#type' => 'hidden',
        '#default_value' => $condition['configuration']['parent'] ?? '',
        '#parents' => array_merge($element['#parents'], [$index, 'parent']),
        '#attributes' => [
          'class' => ['plugin-parent'],
        ],
      ];
      $condition_form['label']['depth'] = [
        '#type' => 'hidden',
        '#default_value' => $condition['configuration']['depth'] ?? '',
        '#parents' => array_merge($element['#parents'], [$index, 'depth']),
        '#attributes' => [
          'class' => ['plugin-depth'],
        ],
      ];
      $condition_form['configuration'] = [
        '#inline_form' => $inline_form,
        '#parents' => array_merge($element['#parents'], [$index, 'configuration']),
      ];
      $condition_form['configuration'] = $inline_form->buildInlineForm($condition_form['configuration'], $form_state);

      // If the plugin provides its own negation, hide our negate checkbox.
      $condition_form['negate_condition'] = [
        '#type' => 'checkbox',
        '#title' => t('Negate'),
        '#parents' => array_merge($element['#parents'], [
          $index,
          'negate_condition',
        ]),
        '#default_value' => $condition['configuration']['negate_condition'] ?? $index,
        '#access' => !isset($condition_form['configuration']['form']['negate']),
      ];

      $condition_form['operations'] = [
        '#type' => 'submit',
        '#name' => 'remove_value' . $index . $condition_plugins_field_name,
        '#value' => t('Remove'),
        '#limit_validation_errors' => [],
        '#submit' => [
          [static::class, 'removeConditionSubmit'],
        ],
        '#value_index' => $index,
        '#ajax' => [
          'callback' => [static::class, 'ajaxConditionsRefresh'],
          'wrapper' => $ajax_wrapper_id,
        ],
        '#parents' => array_merge($element['#parents'], [$index, 'remove']),
      ];

      // @todo need to add #weight value.
      // @see ProductAttributeForm for reading from user input.
      $condition_form['#weight'] = $index;
      $condition_form['sort_weight'] = [
        '#type' => 'weight',
        '#title' => t('Weight'),
        '#title_display' => 'invisible',
        '#delta' => $max_weight,
        '#default_value' => $condition['configuration']['sort_weight'] ?? $index,
        '#parents' => array_merge($element['#parents'], [$index, 'sort_weight']),
        '#attributes' => [
          'class' => ['plugin-weight'],
        ],
      ];
    }

    $element['add_new'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['container-inline'],
      ],
    ];

    $element['add_new']['conditions_id'] = [
      '#title' => 'Select a condition type',
      '#title_display' => 'invisible',
      '#type' => 'select',
      '#default_value' => '',
      '#empty_option' => '- Choose -',
      '#options' => $grouped_definitions,
    ];

    $element['add_new']['add_condition'] = [
      '#type' => 'submit',
      '#value' => 'Add',
      '#name' => 'add_new_condition_' . $condition_plugins_field_name,
      '#ajax' => [
        'callback' => [static::class, 'ajaxRefresh'],
        'wrapper' => $ajax_wrapper_id,
      ],
      // @todo add a validation on the selected condition plugin ID.
      '#validate' => [],
      '#limit_validation_errors' => [$element['#parents']],
      '#submit' => [
        [static::class, 'addNewCondition'],
      ],
      '#states' => [
        'disabled' => [
          'select[name="' . $element['#name'] . '[add_new][conditions_id]"]' => ['value' => ''],
        ],
      ],
    ];

    return $element;
  }

  /**
   * Submits the conditions.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function submitConditions(array &$element, FormStateInterface $form_state) {
    $values = $form_state->getValue($element['#parents']);
    $values = array_filter($values, static function ($key) {
      return is_int($key);
    }, ARRAY_FILTER_USE_KEY);
    $values = array_map(static function (array $condition) {
      $condition['configuration']['parent'] = $condition['parent'];
      $condition['configuration']['depth'] = (int) $condition['depth'];
      $condition['configuration']['sort_weight'] = $condition['sort_weight'];
      $condition['configuration']['negate_condition'] = $condition['negate_condition'];
      unset($condition['parent'], $condition['depth'], $condition['sort_weight'], $condition['negate_condition']);
      return $condition;
    }, $values);
    $form_state->setValueForElement($element, $values);
  }

  public static function addNewCondition(&$form, FormStateInterface $form_state) {
    $element_parents = array_slice($form_state->getTriggeringElement()['#parents'], 0, -1);
    $values = $form_state->getValue($element_parents);

    $condition_plugins_field_name = $form_state->getTriggeringElement()['#parents'][0];

    $conditions = $form_state->get($condition_plugins_field_name);

    /** @var \Drupal\complex_conditions\ConditionManagerInterface $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.complex_conditions');
    $instance = $plugin_manager->createInstance($values['conditions_id']);
    assert($instance instanceof ConditionInterface);

    $conditions[] = [
      'plugin' => $instance->getPluginId(),
      'configuration' => $instance->getConfiguration(),
    ];
    $form_state->set($condition_plugins_field_name, $conditions);
    $form_state->setRebuild();
  }

  public static function removeConditionSubmit(&$form, FormStateInterface $form_state) {
    $condition_plugins_field_name = $form_state->getTriggeringElement()['#parents'][0];

    $value_index = $form_state->getTriggeringElement()['#value_index'];
    $conditions = $form_state->get($condition_plugins_field_name);
    unset($conditions[$value_index]);
    $form_state->set($condition_plugins_field_name, $conditions);
    $form_state->setRebuild();
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(&$form, FormStateInterface $form_state) {
    $element_parents = array_slice($form_state->getTriggeringElement()['#array_parents'], 0, -2);
    return NestedArray::getValue($form, $element_parents);
  }

  /**
   * Ajax callback.
   */
  public static function ajaxConditionsRefresh(&$form, FormStateInterface $form_state) {
    // @todo merge above, but check the triggering element for slice depth.
    $element_parents = array_slice($form_state->getTriggeringElement()['#array_parents'], 0, -3);
    return NestedArray::getValue($form, $element_parents);
  }
}
