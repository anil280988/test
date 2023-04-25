<?php

namespace Drupal\Tests\session_inspector\Functional;

use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Tests\BrowserTestBase;

/**
 * Test the functionality of the session inspector module.
 *
 * @group session_inspector
 */
class SessionInspectorTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'session_inspector',
    'session_inspector_events_test',
  ];

  /**
   * The theme to install as the default for testing.
   *
   * @var string
   */
  public $defaultTheme = 'stark';

  /**
   * Test that an anonymous user can't access a users session page.
   */
  public function testAnonUserCanNotReachTheSessionPage() {
    $this->drupalGet('user/1/sessions');
    $this->assertSession()->responseContains('Access denied');
  }

  /**
   * Test that a user can inspect and delete their own sessions.
   */
  public function testUserWithPermissionsCanInspectSessions() {
    $user = $this->createUser(['inspect own user sessions']);
    $this->drupalLogin($user);

    $this->drupalGet('user/' . $user->id() . '/sessions');

    $this->assertSession()->responseContains('CURRENT');
    $this->assertSession()->responseContains('LOCATION');
    $this->assertSession()->responseContains('TIMESTAMP');
    $this->assertSession()->responseContains('BROWSER');
    $this->assertSession()->responseContains('OPERATIONS');

    // Delete on the delete.
    $this->click('[data-test="session-operation-0"] a');

    $url = $this->getUrl();
    preg_match('/user\/(?<uid>\d*)\/sessions\/(?<sid>.*)\/delete/', $url, $urlMatches);
    $uid = $urlMatches['uid'];
    $sid = $urlMatches['sid'];

    // Click confirm.
    $this->click('[id="edit-submit"]');

    // Ensure that the session event was triggered and that it
    // contains the correct information.
    $this->assertEquals($uid, $this->container->get('state')->get('session_event.uid'));
    $this->assertEquals($sid, $this->container->get('state')->get('session_event.sid'));

    // The response is access denied as the user is logged out.
    $this->assertSession()->responseContains('Access denied');

    // Ensure that we are not logged in anymore.
    $this->drupalGet('user/' . $user->id() . '/edit');
    $this->assertSession()->responseContains('Access denied');
  }

  /**
   * Test that a user cannot access another users sessions page.
   */
  public function testUserCannotAccessAnotherUsersSessionPage() {
    // Create users.
    $user = $this->createUser(['inspect own user sessions']);
    $anotherUser = $this->createUser(['inspect own user sessions']);

    // Log in as first user.
    $this->drupalLogin($user);

    // Access down session page.
    $this->drupalGet('user/' . $user->id() . '/sessions');

    // Attempt to access other session page.
    $this->drupalGet('user/' . $anotherUser->id() . '/edit');
    $this->assertSession()->responseContains('Access denied');
  }

  /**
   * Test that a user can delete all other sessions.
   */
  public function testUserCanDeleteAllOtherSessions() {
    $user = $this->createUser(['inspect own user sessions']);
    $this->drupalLogin($user);

    // "Forget" the current session.
    $this->getSession()->setCookie(\Drupal::service('session_configuration')->getOptions(\Drupal::request())['name']);
    $this->loggedInUser = FALSE;
    \Drupal::currentUser()->setAccount(new AnonymousUserSession());

    // Log back in.
    $this->drupalLogin($user);

    // Go to session pages.
    $this->drupalGet('user/' . $user->id() . '/sessions');

    // Delete all other sessions, which will be there since we have logged
    // in twice.
    $this->click('[data-test="session-operation-delete-all"]');

    // Confirm the all session deletion form.
    $this->click('[id="edit-submit"]');

    // Check that the user is still logged in.
    $this->assertSession()->responseNotContains('Access denied');
    $this->assertSession()->responseContains('CURRENT');
    $this->assertSession()->responseContains('LOCATION');
    $this->assertSession()->responseContains('TIMESTAMP');
    $this->assertSession()->responseContains('BROWSER');
    $this->assertSession()->responseContains('OPERATIONS');
  }

}
