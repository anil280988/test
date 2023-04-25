<?php

namespace Drupal\session_inspector\Plugin\HostnameFormat;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\session_inspector\Plugin\HostnameFormatInterface;

/**
 * Provides basic formatting for hostname.
 *
 * @HostnameFormat(
 *   id = "basic",
 *   name = @Translation("Basic hostname format")
 * )
 */
class BasicHostnameFormat extends PluginBase implements HostnameFormatInterface {

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
    return $hostname;
  }

}
