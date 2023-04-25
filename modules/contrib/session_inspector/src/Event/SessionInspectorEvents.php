<?php

namespace Drupal\session_inspector\Event;

/**
 * Contains all events thrown in the Session Inspector module.
 */
final class SessionInspectorEvents {

  /**
   * Event ID for when a session record is destroyed.
   *
   * @Event
   *
   * @var string
   */
  const SESSION_DESTROYED = 'session_inspector.session_destroyed';

}
