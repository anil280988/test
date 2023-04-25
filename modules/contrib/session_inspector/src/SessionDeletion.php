<?php

namespace Drupal\session_inspector;

use Drupal\Core\Database\Connection;
use Drupal\session_inspector\Event\SessionEvent;
use Drupal\session_inspector\Event\SessionInspectorEvents;
use Drupal\user\Entity\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The class to delete all active sessions service.
 *
 * @package Drupal\session_inspector\Service
 */
class SessionDeletion implements SessionDeletionInterface {

  /**
   * The SessionInspector service.
   *
   * @var \Drupal\session_inspector\SessionInspectorInterface
   */
  protected $sessionInspector;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a SessionDeletion object.
   *
   * @param \Drupal\session_inspector\SessionInspectorInterface $sessionInspector
   *   The SessionInspector service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(SessionInspectorInterface $sessionInspector, Connection $database, EventDispatcherInterface $event_dispatcher) {
    $this->sessionInspector = $sessionInspector;
    $this->database = $database;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAllSessions(User $user): void {
    $sessions = $this->sessionInspector->getSessions($user);
    foreach ($sessions as $session) {
      if ($this->sessionInspector->isCurrentSession($session['sid']) === FALSE) {
        $this->deleteSession($session['sid']);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteSession(string $session_id): void {
    // Get the session data we are about to delete.
    $sessionData = $this->sessionInspector->getSession($session_id);
    $sessionEvent = new SessionEvent($sessionData['uid'], $sessionData['sid'], $sessionData['hostname'], $sessionData['timestamp']);

    // Delete the session.
    $query = $this->database->delete('sessions');
    $query->condition('sid', $session_id);
    $query->execute();

    // Trigger the session event.
    $this->eventDispatcher->dispatch($sessionEvent, SessionInspectorEvents::SESSION_DESTROYED);
  }

}
