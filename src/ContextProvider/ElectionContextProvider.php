<?php

namespace Drupal\election\ContextProvider;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextInterface;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\election\Entity\ElectionInterface;
use Drupal\node\NodeInterface;

/**
 * Class ElectionContextProvider
 */
class ElectionContextProvider implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * Constructs a new ElectionContextProvider.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The current route match object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(RouteMatchInterface $current_route_match, EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->currentRouteMatch = $current_route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $contexts = [];
    foreach ($unqualified_context_ids as $entity_type_id) {
      // Create an optional context definition for election entities.
      $context_definition = EntityContextDefinition::fromEntityTypeId($entity_type_id)
        ->setRequired(FALSE);

      // Cache this context per election on the route.
      $cacheability = new CacheableMetadata();
      $cacheability->setCacheContexts(['route.' . $entity_type_id]);

      // Create a context from the definition and retrieved or created election.
      $context = new Context($context_definition, $this->getFromRoute($entity_type_id));
      $context->addCacheableDependency($cacheability);

      $contexts[$entity_type_id] = $context;
    }

    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    return [
      'election' => EntityContext::fromEntityTypeId('election', $this->t('Election from URL')),
      'election_post' => EntityContext::fromEntityTypeId('election_post', $this->t('Election post from URL')),
    ];
  }

  /**
   * The current route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Gets the current route match object.
   *
   * @return \Drupal\Core\Routing\RouteMatchInterface
   *   The current route match object.
   */
  protected function getCurrentRouteMatch() {
    if (!$this->currentRouteMatch) {
      $this->currentRouteMatch = \Drupal::service('current_route_match');
    }
    return $this->currentRouteMatch;
  }

  /**
   * Gets the entity type manager service.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager service.
   */
  protected function getEntityTypeManager() {
    if (!$this->entityTypeManager) {
      $this->entityTypeManager = \Drupal::entityTypeManager();
    }
    return $this->entityTypeManager;
  }

  /**
   * Retrieves the election entity from the current route.
   *
   * This will try to load the election entity from the route if present. If we are
   * on the election add form, it will return a new election entity with the election
   * type set.
   *
   * @return \Drupal\election\Entity\ElectionInterface|\Drupal\election\Entity\ElectionPostInterface|null
   *   An entity if one could be found or created, NULL otherwise.
   */
  public function getFromRoute($entity_type_id) {
    $route_match = $this->getCurrentRouteMatch();

    // See if the route has a election parameter and try to retrieve it.
    if (($entity = $route_match->getParameter($entity_type_id))) {
      return $entity;
    }

    // Create a new election to use as context if on the election add form.
    elseif ($route_match->getRouteName() == 'entity.' . $entity_type_id . '.add_form') {
      $type = $route_match->getParameter($entity_type_id . '_type');
      return $this->getEntityTypeManager()->getStorage($entity_type_id)->create(['type' => $type->id()]);
    }

    return NULL;
  }
}
