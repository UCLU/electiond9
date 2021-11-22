<?php

namespace Drupal\election\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Defines dynamic local tasks.
 */
class ElectionLocalTasks extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $context_provider = \Drupal::service('election.election_route_context');
    $contexts = $context_provider->getRuntimeContexts(['election']);
    if (!$contexts['election']) {
      return;
    }
    $election = $contexts['election']->getContextValue();

    if ($election) {
      foreach ($election->getElectionType()->getAllowedPostTypes() as $election_post_type) {
        $key = 'election.add_post.' . $election_post_type->id();
        $this->derivatives[$key] = $base_plugin_definition;
        $this->derivatives[$key]['title'] = t('Add @label', ['@label' => $election_post_type->getNaming()]);
        $this->derivatives[$key]['route_name'] = 'entity.election_post.add_to_election';

        $this->derivatives[$key]['route_parameters'] = [
          'election' => $election->id(),
          'election_post_type' => $election_post_type->id(),
        ];
      }
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }
}
