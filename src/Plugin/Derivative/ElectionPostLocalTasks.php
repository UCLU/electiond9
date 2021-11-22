<?php

namespace Drupal\election\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\election\Entity\ElectionPost;

/**
 * Defines dynamic local tasks.
 */
class ElectionPostLocalTasks extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $context_provider = \Drupal::service('election.election_route_context');
    $contexts = $context_provider->getRuntimeContexts(['election_post']);
    if (!$contexts['election_post']) {
      return;
    }
    $election_post = $contexts['election_post']->getContextValue();
    if (is_string($election_post)) {
      $election_post = ElectionPost::load($election_post);
    }

    if ($election_post) {
      foreach ($election_post->getElectionPostType()->getAllowedCandidateTypes() as $election_candidate_type) {
        $key = 'election.add_candidate.' . $election_candidate_type->id();
        $this->derivatives[$key] = $base_plugin_definition;
        $this->derivatives[$key]['title'] = t('Add @label', ['@label' => $election_candidate_type->getNaming()]);
        $this->derivatives[$key]['route_name'] = 'entity.election_candidate.add_to_election_post';

        $this->derivatives[$key]['route_parameters'] = [
          'election_post' => $election_post->id(),
          'election_candidate_type' => $election_candidate_type->id(),
        ];
      }
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }
}
