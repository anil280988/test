<?php

namespace Drupal\session_inspector\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * A data object to pass information about the session to the event.
 *
 * @package Drupal\session_inspector\Event
 */
class SessionEvent extends Event {

  /**
   * The user ID.
   *
   * @var int
   */
  protected $uid;

  /**
   * The (hashed) session ID.
   *
   * @var string
   */
  protected $sid;

  /**
   * The session hostname.
   *
   * @var string
   */
  protected $hostname;

  /**
   * The session timestamp.
   *
   * @var int
   */
  protected $timestamp;

  /**
   * Constructs a SessionEvent object.
   *
   * @param int $uid
   *   The user ID.
   * @param string $sid
   *   The (hashed) session ID.
   * @param string $hostname
   *   The session hostname.
   * @param int $timestamp
   *   The session timestamp.
   */
  public function __construct($uid, $sid, $hostname, $timestamp) {
    $this->uid = $uid;
    $this->sid = $sid;
    $this->hostname = $hostname;
    $this->timestamp = $timestamp;
  }

  /**
   * Get the user ID of the sesion.
   *
   * @return int
   *   The user ID.
   */
  public function getUid(): int {
    return $this->uid;
  }

  /**
   * Set the user ID of the session.
   *
   * @param int $uid
   *   The user ID.
   */
  public function setUid(int $uid) {
    $this->uid = $uid;
  }

  /**
   * Get the session ID of the session.
   *
   * @return string
   *   The session ID.
   */
  public function getSid(): string {
    return $this->sid;
  }

  /**
   * Set the session ID of the session.
   *
   * @param string $sid
   *   The session ID.
   */
  public function setSid(string $sid) {
    $this->sid = $sid;
  }

  /**
   * Get the hostname of the session.
   *
   * @return string
   *   The hostname.
   */
  public function getHostname(): string {
    return $this->hostname;
  }

  /**
   * Set the hostname of the session.
   *
   * @param string $hostname
   *   The hostname.
   */
  public function setHostname(string $hostname) {
    $this->hostname = $hostname;
  }

  /**
   * Get the timestamp of the session.
   *
   * @return int
   *   The timestamp.
   */
  public function getTimestamp(): int {
    return $this->timestamp;
  }

  /**
   * Set the timestamp of the session.
   *
   * @param int $timestamp
   *   The timestamp.
   */
  public function setTimestamp(int $timestamp) {
    $this->timestamp = $timestamp;
  }

}
