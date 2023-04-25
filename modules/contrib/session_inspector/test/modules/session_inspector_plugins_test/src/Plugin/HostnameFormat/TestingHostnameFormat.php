<?php

namespace Drupal\session_inspector_plugins_test\Plugin\HostnameFormat;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\session_inspector\Plugin\HostnameFormatInterface;

/**
 * Provides a testing formatting for hostname.
 *
 * @HostnameFormat(
 *   id = "testing",
 *   name = @Translation("Testing hostname format")
 * )
 */
class TestingHostnameFormat extends PluginBase implements HostnameFormatInterface {

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
  public function formatHostname(string $hostname):string {
    // Deliberately return a unique string to prove the plugin is active.
    return '64ad3de8-af3c-49b1-9ad0-17ea2231724a';
  }

}
