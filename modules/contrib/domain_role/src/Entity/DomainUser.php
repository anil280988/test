<?php
/**
 * Created by PhpStorm.
 * User: john
 * Date: 2/24/19
 * Time: 10:41 PM
 */

namespace Drupal\domain_role\Entity;


use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\user\Entity\User;
use Drupal\user\RoleInterface;


class DomainUser extends User {

  private $allRoles = [];

  /**
   *
   * Override getRoles to return only roles relevant to the current domain.
   *
   * @param bool $exclude_locked_roles
   *
   * @return array
   *
   */
  function getRoles($exclude_locked_roles = FALSE) {
    $user_roles = $this->get('roles');
    if (!count($this->allRoles)){
      // store the roles this user has across all domains in case the user is saved.
      $this->allRoles = $user_roles;
    }
    $domain = $this->getDomain();
    $domain_roles = $this->getDomainRoles();
    $all_roles = user_role_names(TRUE);
    $new_roles = [];

    // Users with an ID always have the authenticated user role.
    if (!$exclude_locked_roles) {
      if ($this->isAuthenticated()) {
        $new_roles[] = RoleInterface::AUTHENTICATED_ID;
      }
      else {
        $new_roles[] = RoleInterface::ANONYMOUS_ID;
      }
    }

    foreach ($user_roles as $role) {
      if (in_array($role->target_id, array_keys($all_roles)) && !in_array($role->target_id, $domain_roles)) {
        // Authenticated user is still part of this array...
        if ($role->target_id !== RoleInterface::AUTHENTICATED_ID) {
          $new_roles[] = $role->target_id;
        }
      }
      else {
        if (strpos($role->target_id, $domain) === 0) {
          $new_roles[] = substr($role->target_id, strlen($domain) + 1);
        }
      }
    }

    return $new_roles;
  }

  /**
   * Override core addRole to track domain-specific roles.
   *
   * @param $rid
   */
  public function addRole($rid) {
    if (in_array($rid, [RoleInterface::AUTHENTICATED_ID, RoleInterface::ANONYMOUS_ID])) {
      throw new \InvalidArgumentException('Anonymous or authenticated role ID must not be assigned manually.');
    }
    $domain = $this->getDomain();
    $domain_roles = $this->getDomainRoles();

    $role_name = in_array($rid, $domain_roles) ? $domain . '-' . $rid : $rid;

    $roles = array_map(function($role) { return $role->target_id; }, $this->get('roles'));
    $roles[] = $role_name;
    $this->set('roles', array_unique($roles));
  }

  /**
   * Override core removeRole to only remove a role for an individual domain.
   *
   * @param $rid
   */
  public function removeRole($rid) {
    $domain = $this->getDomain();
    $domain_roles = $this->getDomainRoles();
    $roles = array_map(function($role) { return $role->target_id; }, $this->get('roles'));

    $role_name = in_array($rid, $domain_roles) ? $domain . '-' . $rid : $rid;

    $this->set('roles', array_diff($roles, [$role_name]));
  }

  /**
   * Override preSave to store domain-id along with role-id.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *
   * @throws \Exception
   */
  public function preSave(EntityStorageInterface $storage) {
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $prev_roles */
    $prev_roles = $this->allRoles;
    $roles = $this->get('roles');
    $domain_roles = $this->getDomainRoles();

    foreach ($this->get('roles') as $index => $role) {
      if (in_array($role->target_id, [RoleInterface::ANONYMOUS_ID, RoleInterface::AUTHENTICATED_ID])) {
        $this->get('roles')->offsetUnset($index);
      }
      if (in_array($role->target_id, $domain_roles)) {
        $role->set('target_id', _domain_role_role_id($role->target_id));
      }
    }
    // now append any roles from other domains
    foreach ($prev_roles as $role) {
      if (!in_array($role->target_id, array_keys(domain_role_user_roles(TRUE)))) {
        $roles->appendItem($role->getValue());
      }
    }
    // Store account cancellation information.
    foreach (['user_cancel_method', 'user_cancel_notify'] as $key) {
      if (isset($this->{$key})) {
        \Drupal::service('user.data')->set('user', $this->id(), substr($key, 5), $this->{$key});
      }
    }
  }

  /**
   * Look up current domain -- cannot use dependency injection due to https://www.drupal.org/node/2913224
   * (results in circular reference exception).
   * @return string
   */
  private function getDomain() {
    /** @var \Drupal\domain\DomainNegotiatorInterface $negotiator */
    $negotiator = \Drupal::service('domain.negotiator');
    return $negotiator->getActiveId();
  }

  private function getDomainRoles() {
    $domain_roles = \Drupal::service('config.factory')->get('domain_role.roles')->get('domain_roles');
    return is_array($domain_roles) ? array_filter($domain_roles) : [];
  }
}