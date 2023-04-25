<?php

namespace Drupal\session_inspector_events_test\EventSubscriber;

use Drupal\Core\State\StateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\session_inspector\Event\SessionInspectorEvents;
use Drupal\session_inspector\Event\SessionEvent;

/**
 * An event subscriber for the session inspector events.
 *
 * @package Drupal\session_inspector_events_test\EventSubscriber
 */
class SessionInspectorEventsTest implements EventSubscriberInterface {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a SessionInspectorEventsTest object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[SessionInspectorEvents::SESSION_DESTROYED] = [
      'onSessionDestroyed',
      10,
    ];
    return $events;
  }

  /**
   * Event callback when a session is destroyed.
   *
   * @param \Drupal\session_inspector\Event\SessionEvent $event
   *   The event data.
   */
  public function onSessionDestroyed(SessionEvent $event) {
    // Set a state so that we can detect that the event triggered.
    $this->state->set('session_event.uid', $event->getUid());
    $this->state->set('session_event.sid', $event->getSid());
  }

}
