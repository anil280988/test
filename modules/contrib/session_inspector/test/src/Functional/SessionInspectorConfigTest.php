<?php

namespace Drupal\Tests\session_inspector\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test the functionality of the session inspector module configuration form.
 *
 * @group session_inspector
 */
class SessionInspectorConfigTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'session_inspector',
    'session_inspector_plugins_test',
  ];

  /**
   * The theme to install as the default for testing.
   *
   * @var string
   */
  public $defaultTheme = 'stark';

  /**
   * Test that an anonymous user can't access the configuration form.
   */
  public function testAnonUserCanNotReachTheConfigForm() {
    $this->drupalGet('admin/config/people/session_inspector');
    $this->assertSession()->responseContains('Access denied');
  }

  /**
   * Test that a user can inspect and delete their own sessions.
   */
  public function testAdminUserCanEditConfig() {
    $permissions = [
      'administer session inspector configuration',
      'inspect own user sessions',
    ];
    $user = $this->createUser($permissions);
    $this->drupalLogin($user);

    $this->drupalGet('admin/config/people/session_inspector');

    // Update the configuration of the module.
    $input = [];
    $input['browser_format'] = 'testing';
    $input['hostname_format'] = 'testing';

    $this->submitForm($input, 'Save configuration');

    // Ensure that the configuration options are now set.
    $this->assertEquals('testing', $this->container->get('config.factory')->get('session_inspector.settings')->get('browser_format'));
    $this->assertEquals('testing', $this->container->get('config.factory')->get('session_inspector.settings')->get('hostname_format'));

    // Visit the sessions page.
    $this->drupalGet('user/' . $user->id() . '/sessions');

    // Browser plugin.
    $this->assertSession()->responseContains('7f7090b5-2440-47cb-9cb0-e8b4e0e676eb');

    // Hostname plugin.
    $this->assertSession()->responseContains('64ad3de8-af3c-49b1-9ad0-17ea2231724a');
  }

}
