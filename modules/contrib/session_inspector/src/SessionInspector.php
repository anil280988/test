<?php

namespace Drupal\session_inspector;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;

/**
 * Implements SessionInspectorInterface.
 *
 * Allows sessions to be inspected and deleted.
 *
 * @package Drupal\session_inspector
 */
class SessionInspector implements SessionInspectorInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The module manager service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleManager;

  /**
   * The session manager service.
   *
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  protected $sessionManager;

  /**
   * Constructs a SessionInspector object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleManager
   *   The module manager service.
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   *   The session manager service.
   */
  public function __construct(Connection $database, ModuleHandlerInterface $moduleManager, SessionManagerInterface $session_manager) {
    $this->database = $database;
    $this->moduleManager = $moduleManager;
    $this->sessionManager = $session_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getSessions(AccountInterface $account):array {
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $this->database->select('sessions', 's');
    $query->fields('s', ['uid', 'sid', 'hostname', 'timestamp', 'session']);
    $query->condition('s.uid', $account->id());
    $query->orderBy('timestamp', 'DESC');

    $results = $query->execute()->fetchAll();

    $sessions = [];

    foreach ($results as $id => $result) {
      // Set a default value for the browser string.
      $browser = '';

      try {
        // Extract the session metadata.
        $sessionData = SessionUnserializer::unserialize($result->session);

        foreach ($sessionData as $data) {
          if ($this->isMasqueradeSession($data) === TRUE) {
            // This is a masquerade session, so ignore.
            continue 2;
          }

          if (isset($data['session_inspector_browser'])) {
            // Extract the browser data.
            $browser = $data['session_inspector_browser'];
          }
        }
      }
      catch (\Exception $e) {
        // Unable to extract session data.
      }

      $sessions[$id] = [
        'uid' => $result->uid,
        'sid' => $result->sid,
        'hostname' => $result->hostname,
        'timestamp' => $result->timestamp,
        'browser' => $browser,
      ];
    }

    return $sessions;
  }

  /**
   * {@inheritdoc}
   */
  public function getSession($sid): array {
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $this->database->select('sessions', 's');
    $query->fields('s', ['uid', 'sid', 'hostname', 'timestamp']);
    $query->condition('s.sid', $sid);

    return $query->execute()->fetch(\PDO::FETCH_ASSOC);
  }

  /**
   * {@inheritdoc}
   */
  public function isCurrentSession(string $sessionId): bool {
    return Crypt::hashBase64($this->sessionManager->getId()) === $sessionId;
  }

  /**
   * Returns true if the session data is from a masquerading session.
   *
   * @param array $data
   *   The unserialized session data.
   *
   * @return bool
   *   True if the session data is from a masquerading session.
   */
  protected function isMasqueradeSession(array $data):bool {
    if (!$this->moduleManager->moduleExists('masquerade')) {
      return FALSE;
    }
    if (isset($data['masquerading']) && $data['masquerading'] == 1) {
      return TRUE;
    }
    return FALSE;
  }

}
