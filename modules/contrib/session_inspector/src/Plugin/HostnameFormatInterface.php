<?php

namespace Drupal\session_inspector\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Defines the interface for hostname format plugins.
 *
 * @package Drupal\session_inspector\Plugin
 */
interface HostnameFormatInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * Format the hostname.
   *
   * @param string $hostname
   *   The hostname, normally in an IP address format.
   *
   * @return string
   *   The formatted hostname.
   */
  public function formatHostname(string $hostname):string;

}
