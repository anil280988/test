<?php

namespace Drupal\rest_log\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Rest log entity entities.
 */
class restLogEntityViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
