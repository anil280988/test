<?php

namespace Drupal\session_inspector\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a host format plugin.
 *
 * Plugin Namespace: Plugin\HostnameFormat.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class HostnameFormat extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the host format plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

}
