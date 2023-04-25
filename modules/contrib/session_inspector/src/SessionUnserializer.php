<?php

namespace Drupal\session_inspector;

/**
 * A utility class to unserialize PHP session data.
 *
 * Adapted from https://www.php.net/manual/en/function.session-decode.php#108037
 *
 * @package Drupal\session_inspector
 */
class SessionUnserializer {

  /**
   * Method to unserialize a string of PHP session data.
   *
   * This method will detect what type of session data is stored and hand off
   * the extraction to other methods.
   *
   * @param string $session_data
   *   The session data.
   *
   * @return array
   *   The unserialized array of session data.
   *
   * @throws \Exception
   */
  public static function unserialize($session_data) {
    $method = ini_get("session.serialize_handler");

    switch ($method) {
      case "php":
        return self::unserializePhp($session_data);

      case "php_binary":
        return self::unserializePhpBinary($session_data);

      default:
        throw new \Exception("Unsupported session.serialize_handler: " . $method . ". Supported: php, php_binary");
    }
  }

  /**
   * Extract the session data from a PHP session string.
   *
   * @param string $session_data
   *   The session data.
   *
   * @return array
   *   The unserialized array of session data.
   *
   * @throws \Exception
   */
  private static function unserializePhp($session_data) {
    $return_data = [];
    $offset = 0;

    while ($offset < strlen($session_data)) {
      if (!strstr(substr($session_data, $offset), "|")) {
        throw new \Exception("invalid data, remaining: " . substr($session_data, $offset));
      }
      $pos = strpos($session_data, "|", $offset);
      $num = $pos - $offset;
      $varname = substr($session_data, $offset, $num);
      $offset += $num + 1;
      $data = unserialize(substr($session_data, $offset), ['allowed_classes' => FALSE]);
      $return_data[$varname] = $data;
      $offset += strlen(serialize($data));
    }

    return $return_data;
  }

  /**
   * Extract the session data from a PHP Binary session string.
   *
   * @param string $session_data
   *   The session data.
   *
   * @return array
   *   The unserialized array of session data.
   *
   * @throws \Exception
   */
  private static function unserializePhpBinary($session_data) {
    $return_data = [];
    $offset = 0;

    while ($offset < strlen($session_data)) {
      $num = ord($session_data[$offset]);
      $offset += 1;
      $varname = substr($session_data, $offset, $num);
      $offset += $num;
      $data = unserialize(substr($session_data, $offset), ['allowed_classes' => FALSE]);
      $return_data[$varname] = $data;
      $offset += strlen(serialize($data));
    }

    return $return_data;
  }

}
