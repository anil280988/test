<?php

namespace Drupal\session_inspector\Plugin\BrowserFormat;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\session_inspector\Plugin\BrowserFormatInterface;

/**
 * Provides basic formatting for browser details.
 *
 * @BrowserFormat(
 *   id = "basic",
 *   name = @Translation("Basic browser format")
 * )
 */
class BasicBrowserFormat extends PluginBase implements BrowserFormatInterface {

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
    return $browser ?: $this->t('Unknown');
  }

}
