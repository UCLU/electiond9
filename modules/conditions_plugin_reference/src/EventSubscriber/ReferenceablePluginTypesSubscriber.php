<?php

namespace Drupal\conditions_plugin_reference\EventSubscriber;

use Drupal\conditions_plugin_reference\Event\ConditionsEvents;
use Drupal\conditions_plugin_reference\Event\ReferenceablePluginTypesEvent;
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
    $types['conditions_plugin_reference'] = $this->t('Condition');
    $event->setPluginTypes($types);
  }
}
