<?php

namespace Drupal\Tests\password_strength\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\password_strength;

/**
 * Tests password strength behaviors from Password Strength library.
 *
 * @group password_strength
 */
class PasswordStrengthTests extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'password_policy',
    'password_strength',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test password strength behaviors.
   *
   * @group ps_maintester
   */
  public function testPasswordStrengthTests() {
    // Create user with permission to create policy.
    $user1 = $this->drupalCreateUser([
      'administer site configuration',
      'administer users',
      'administer permissions',
    ]);
    $this->drupalLogin($user1);

    $user2 = $this->drupalCreateUser();

    // Create role.
    $rid = $this->drupalCreateRole([]);

    // Set role for user.
    $edit = [
      'roles[' . $rid . ']' => $rid,
    ];
    $this->drupalGet("user/" . $user2->id() . "/edit");
    $this->submitForm($edit, 'Save');

    // Create new password reset policy for role.
    $this->drupalGet("admin/config/security/password-policy/add");
    $edit = [
      'id' => 'test',
      'label' => 'test',
      'password_reset' => '1',
    ];
    // Set reset and policy info.
    $this->submitForm($edit, 'Next');

    $this->assertSession()->pageTextContains('No constraints have been configured.');

    // Fill out length constraint for test policy.
    $edit = [
      'strength_score' => '4',
    ];
    $this->drupalGet('admin/config/system/password_policy/constraint/add/test/password_strength_constraint');
    $this->submitForm($edit, 'Save');

    $this->assertSession()->pageTextContains('password_strength_constraint');
    $this->assertSession()->pageTextContains('Password Strength minimum score of 4');

    // Go to the next page.
    $this->submitForm([], 'Next');
    // Set the roles for the policy.
    $edit = [
      'roles[' . $rid . ']' => $rid,
    ];
    $this->submitForm($edit, 'Finish');

    $this->assertSession()->pageTextContains('The Password Policy test has been added.');

    $this->drupalLogout();
    $this->drupalLogin($user2);

    // Change own password with one not very complex.
    $edit = [];
    $edit['pass[pass1]'] = '1';
    $edit['pass[pass2]'] = '1';
    $edit['current_pass'] = $user2->pass_raw;
    $this->drupalGet("user/" . $user2->id() . "/edit");
    $this->submitForm($edit, 'Save');

    // Verify we see an error.
    $this->assertSession()->pageTextContains('The password does not satisfy the password policies');

    // Change own password with one strong enough.
    $edit = [];
    $edit['pass[pass1]'] = 'aV3ryC*mplexPassword1nd33d!';
    $edit['pass[pass2]'] = 'aV3ryC*mplexPassword1nd33d!';
    $edit['current_pass'] = $user2->pass_raw;
    $this->drupalGet("user/" . $user2->id() . "/edit");
    $this->submitForm($edit, 'Save');

    // Verify we see do not error.
    $this->assertSession()->pageTextNotContains('The password does not satisfy the password policies');

    $this->drupalLogout();
  }

}
