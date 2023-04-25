<?php

namespace Drupal\session_inspector\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a browser format plugin.
 *
 * Plugin Namespace: Plugin\BrowserFormat.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class BrowserFormat extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the browser format plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

}
