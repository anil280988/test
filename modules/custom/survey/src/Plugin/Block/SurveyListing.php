<?php

namespace Drupal\survey\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Survey listing' Block.
 *
 * @Block(
 *   id = "survey_listing",
 *   admin_label = @Translation("Survey listing"),
 *   category = @Translation("Survey listing"),
 * )
 */
class SurveyListing extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => $this->t('Display Survey.'),
    ];
  }
}
