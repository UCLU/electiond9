<?php

namespace Drupal\election_conditions\EventSubscriber;

use Drupal\commerce\Event\ReferenceablePluginTypesEvent;
use Drupal\conditions_plugin_reference\Event\ConditionsEvents;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ReferenceablePluginTypesSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConditionsEvents::REFERENCEABLE_PLUGIN_TYPES][] = ['onPluginTypes'];
    return $events;
  }

  /**
   * Registers the 'election_post_condition' plugin type as referenceable.
   *
   * @param \Drupal\commerce\Event\ReferenceablePluginTypesEvent $event
   *   The event.
   */
  public function onPluginTypes(ReferenceablePluginTypesEvent $event) {
    $types = $event->getPluginTypes();
    $types['election_post_condition'] = $this->t('Election post condition');
    $event->setPluginTypes($types);
  }
}
