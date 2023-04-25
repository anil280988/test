<?php

namespace Drupal\password_strength;

use ZxcvbnPhp\Zxcvbn;

/**
 * Base class PasswordStrength.
 *
 * @package Drupal\password_strength
 */
class PasswordStrength {
  /**
   * Member variable.
   *
   * @var \ZxcvbnPhp\Zxcvbn
   */
  protected $zxcvbnPhp;

  /**
   * {@inheritdoc}
   *
   * Instantiate the Zxcvbn and save it in member variable.
   */
  public function __construct() {
    $this->zxcvbnPhp = new Zxcvbn();
  }

  /**
   * Calculate password strength via non-overlapping minimum entropy patterns.
   *
   * @param string $password
   *   Password to measure.
   * @param array $userInputs
   *   Optional user inputs.
   *
   * @return array
   *   Strength result array with keys:
   *     password
   *     entropy
   *     match_sequence
   *     score
   */
  public function passwordStrength($password, array $userInputs = []) {
    return $this->zxcvbnPhp->passwordStrength($password, $userInputs);
  }

}
