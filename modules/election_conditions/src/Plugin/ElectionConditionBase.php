<?php

namespace Drupal\election_conditions\Plugin;

use Drupal\conditions_plugin_reference\Plugin\ConditionsPluginReference\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\election\Entity\ElectionPost;

/**
 * Base class for Election post condition plugin plugins.
 */
abstract class ElectionConditionBase extends ConditionBase {

  public function requiredParameters(): array {
    return [
      'phase' => 'string',
    ];
  }

  public function evaluateRequirements(EntityInterface $entity, AccountInterface $account, $parameters = []) {
    $this->assertParameters($parameters);
    $requirements = [];

    // This is where you would generate and evaluate your requirements.

    $this->dispatchRequirementEvents($requirements);
    return $requirements;
  }

  public function getCacheContextsForEligibility(ElectionPost $post, AccountInterface $account) {
  }
}
