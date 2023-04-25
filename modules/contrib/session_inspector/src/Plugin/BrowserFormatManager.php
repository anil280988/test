<?php

namespace Drupal\session_inspector\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Component\Plugin\FallbackPluginManagerInterface;

/**
 * A plugin manager class for browser format plugins.
 *
 * @package Drupal\session_inspector\Plugin
 */
class BrowserFormatManager extends DefaultPluginManager implements FallbackPluginManagerInterface {

  /**
   * Constructs a BrowserFormatManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/BrowserFormat',
      $namespaces,
      $module_handler,
      'Drupal\session_inspector\Plugin\BrowserFormatInterface',
      'Drupal\session_inspector\Annotation\BrowserFormat'
    );
    $this->alterInfo('browser_format_info');
    $this->setCacheBackend($cache_backend, 'browser_format_info_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return 'basic';
  }

}
