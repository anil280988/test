<?php

namespace Drupal\session_inspector;

use Drupal\Core\Session\AccountInterface;

/**
 * An interface for inspecting and deactivating sessions.
 *
 * @package Drupal\session_inspector
 */
interface SessionInspectorInterface {

  /**
   * Get all of the sesions belonging to the user passed.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to use to find the sessions.
   *
   * @return array
   *   An array of sessions information.
   */
  public function getSessions(AccountInterface $account): array;

  /**
   * Get a single session.
   *
   * @param string $sid
   *   The session ID.
   *
   * @return array
   *   The session data.
   */
  public function getSession($sid): array;

  /**
   * Is a session ID the currently used session?
   *
   * @param string $sessionId
   *   The session ID to inspect.
   *
   * @return bool
   *   Returns true if the session ID is the currently used one.
   */
  public function isCurrentSession(string $sessionId): bool;

}
