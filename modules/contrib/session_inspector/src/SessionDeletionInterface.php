<?php

namespace Drupal\session_inspector;

use Drupal\user\Entity\User;

/**
 * An interface for deleting sessions.
 *
 * @package Drupal\session_inspector
 */
interface SessionDeletionInterface {

  /**
   * Delete all active sessions, except the current session.
   *
   * @param \Drupal\user\Entity\User $user
   *   The current user.
   */
  public function deleteAllSessions(User $user): void;

  /**
   * Deletes a session with a given session ID.
   *
   * @param string $session_id
   *   The session ID to delete.
   */
  public function deleteSession(string $session_id): void;

}
