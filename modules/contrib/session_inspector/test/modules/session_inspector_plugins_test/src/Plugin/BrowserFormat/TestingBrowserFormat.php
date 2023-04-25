<?php

namespace Drupal\session_inspector_plugins_test\Plugin\BrowserFormat;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\session_inspector\Plugin\BrowserFormatInterface;

/**
 * Provides a testing formatting for browser details.
 *
 * @BrowserFormat(
 *   id = "testing",
 *   name = @Translation("Testing browser format")
 * )
 */
class TestingBrowserFormat extends PluginBase implements BrowserFormatInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formatBrowser(string $browser):string {
    // Deliberately return a unique string to prove the plugin is active.
    return '7f7090b5-2440-47cb-9cb0-e8b4e0e676eb';
  }

}
