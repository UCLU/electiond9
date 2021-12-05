<?php

namespace Drupal\conditions_plugin_reference;

use Drupal\conditions_plugin_reference\Plugin\ConditionsPluginReference\Condition\AndOperator;
use Drupal\conditions_plugin_reference\Plugin\ConditionsPluginReference\Condition\OrOperator;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

class ConditionsRenderer {
  public function requirementsTable(array $requirements) {

    $rows = [];
    foreach ($requirements as $requirement) {
      $row = [
        'Requirement' => ['data' => [
          '#markup' => $requirement->getLabel(),
          '#suffix' => $requirement->getDescription() ? '<div class="form-item__description">' . $requirement->getDescription() . '</div>' : '',
        ]],
        'Pass' => $requirement->isPassed() ? '✔️' : '❌',
      ];

      $rows[] = $row;
    }

    return [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => count($rows) > 0 ? array_keys($rows[0]) : [],
    ];
  }
}
