<?php

namespace Drupal\session_inspector\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Defines the interface for browser format plugins.
 *
 * @package Drupal\session_inspector\Plugin
 */
interface BrowserFormatInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * Format the browser string into something useful.
   *
   * @param string $browser
   *   The browser string.
   *
   * @return string
   *   The formatted browser string.
   */
  public function formatBrowser(string $browser):string;

}
