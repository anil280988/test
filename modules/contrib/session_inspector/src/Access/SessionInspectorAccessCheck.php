<?php

namespace Drupal\session_inspector\Access;

use Drupal\user\UserInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Checks access for displaying the session inspection and management page.
 */
class SessionInspectorAccessCheck implements AccessInterface {

  /**
   * Session inspector access check.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user who the sessions page belongs to.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(UserInterface $user, AccountInterface $account) {
    if ($account->hasPermission('inspect other user sessions')) {
      return AccessResult::allowed();
    }

    if ($account->hasPermission('inspect own user sessions') && $account->id() == $user->id()) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

}
