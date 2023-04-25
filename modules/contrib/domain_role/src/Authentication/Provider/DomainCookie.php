<?php


namespace Drupal\domain_role\Authentication\Provider;


use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionConfigurationInterface;
use Drupal\Core\Session\UserSession;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\user\Authentication\Provider\Cookie;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Overrides core Cookie authentication method to map domain-specific roles back
 * to the original role names.
 *
 */
class DomainCookie extends Cookie {

  /**
   * @var DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  public function __construct(SessionConfigurationInterface $session_configuration, Connection $connection,
    DomainNegotiatorInterface $domainNegotiator) {
    parent::__construct($session_configuration, $connection);
    $this->domainNegotiator = $domainNegotiator;
  }

  /**
   * Override Cookie authentication plugin to rewrite role names.
   */
  public function getUserFromSession(SessionInterface $session) {
    if ($uid = $session->get('uid')) {
      // @todo Load the User entity in SessionHandler so we don't need queries.
      // @see https://www.drupal.org/node/2345611
      $values = $this->connection
        ->query('SELECT * FROM {users_field_data} u WHERE u.uid = :uid AND u.default_langcode = 1', [':uid' => $uid])
        ->fetchAssoc();

      // Check if the user data was found and the user is active.
      if (!empty($values) && $values['status'] == 1) {
        // Add the user's roles.
        $rids = $this->connection
          ->query('SELECT roles_target_id FROM {user__roles} WHERE entity_id = :uid', [':uid' => $values['uid']])
          ->fetchCol();
        $roles = array_merge([AccountInterface::AUTHENTICATED_ROLE], $rids);
        // Currently when session is loaded, domain has not been negotiated so
        // reset this now.
        $domain = $this->domainNegotiator->getActiveDomain(TRUE)->id();
        $domain_roles = domain_role_ids();
        $all_roles = user_roles();
        $values['roles'] = [];
        foreach ($roles as $role) {
          if (in_array($role, array_keys($all_roles)) && !in_array($role, $domain_roles)) {
            $values['roles'][] = $role;
          }
          elseif (strpos($role, $domain) === 0) {
            $role = substr($role, strlen($domain) + 1);
            if (in_array($role, $domain_roles)) {
              $values['roles'][] = $role;
            }
          }
        }

        return new UserSession($values);
      }
    }

    // This is an anonymous session.
    return NULL;
  }
}