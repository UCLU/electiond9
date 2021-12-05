<?php

namespace Drupal\conditions_plugin_reference;

use Drupal\conditions_plugin_reference\Plugin\ConditionsPluginReference\Condition\AndOperator;
use Drupal\conditions_plugin_reference\Plugin\ConditionsPluginReference\Condition\OrOperator;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

class ConditionsRenderer {
  public function requirementsTable(array $requirements) {

    $rows = [];
    $groupingsDone = [];

    foreach ($requirements as $requirement) {
      $grouping = $requirement->getGroup() && $requirement->getGroup() != 'ungrouped' ? $requirement->getGroup() : '';
      if ($grouping) {
        if (!in_array($grouping, $groupingsDone)) {
          $row = [];
          $row['Requirement'] = [
            'data' => [
              '#markup' => '<b>' . $grouping . '</b>',
            ],
            'class' => []
          ];
          $row['Pass'] = ''; // $requirement->isPassed() ? '✔️' : '❌';
          $rows[] = $row;
        }
      }

      $row = [];
      $row['Requirement'] = [
        'data' => [
          '#prefix' => $grouping ? '&nbsp;&nbsp;&nbsp;•&nbsp;' : '',
          '#markup' => $requirement->getLabel(),
          '#suffix' => $requirement->getDescription() ? '<div class="form-item__description">' . $requirement->getDescription() . '</div>' : '',
        ],
        'class' => []
      ];
      $row['Pass'] = $requirement->isPassed() ? '✔️' : '❌';
      $rows[] = $row;
    }

    $headers = [];
    $headers[] = [
      'data' => t('Requirement'),
    ];
    $headers[] = t('Pass');

    return [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $headers,
    ];
  }
}
