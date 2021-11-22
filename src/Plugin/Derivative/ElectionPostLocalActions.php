<?php

namespace Drupal\election\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\election\Entity\ElectionPost;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines dynamic local tasks.
 */
class ElectionPostLocalActions extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an entity local actions deriver.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static($container->get('entity_type.manager'));
  }

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
      $cacheTags = Cache::mergeTags($election_post->getCacheTags(), $election_post->getElectionPostType()->getCacheTags());
      $candidateTypes = $election_post->getElectionPostType()->getAllowedCandidateTypes();
      foreach ($candidateTypes as $election_candidate_type) {
        $key = 'election.add_candidate.' . $election_candidate_type->id();
        $this->derivatives[$key] = $base_plugin_definition;
        $this->derivatives[$key]['appears_on'] = ["entity.election_post.canonical"];
        $this->derivatives[$key]['title'] = t('@label', ['@label' => $election_candidate_type->getActionNaming($election_post)]);
        $this->derivatives[$key]['route_name'] = 'entity.election_candidate.add_to_election_post';

        $this->derivatives[$key]['route_parameters'] = [
          'election_post' => $election_post->id(),
          'election_candidate_type' => $election_candidate_type->id(),
        ];

        $this->derivatives[$key]['cache_tags'] = Cache::mergeTags($cacheTags, $election_candidate_type->getCacheTags());
      }
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }
}
