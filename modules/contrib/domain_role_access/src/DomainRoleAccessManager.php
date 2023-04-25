<?php

namespace Drupal\domain_role_access;

use Drupal\user\Entity\User;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\domain\DomainInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\domain_access\DomainAccessManager;
use Drupal\domain_access\DomainAccessManagerInterface;

/**
 * Decorates Drupal\domain_access\DomainAccessManager with possibility.
 *
 * To assign domain permissions based on user roles.
 */
class DomainRoleAccessManager extends DomainAccessManager {


  /**
   * Parent service object.
   *
   * @var \Drupal\domain_access\DomainAccessManagerInterface
   */
  protected $parent;

  /**
   * Constructs a DomainRoleAccessManager object.
   *
   * @param \Drupal\domain_access\DomainAccessManagerInterface $parent
   *   Parent service object.
   * @param \Drupal\domain\DomainNegotiatorInterface $negotiator
   *   The domain negotiator.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The Drupal module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(DomainAccessManagerInterface $parent, DomainNegotiatorInterface $negotiator, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($negotiator, $module_handler, $entity_type_manager);
    $this->parent = $parent;
  }

  /**
   * {@inheritdoc}
   */
  public static function getAccessValues(FieldableEntityInterface $entity, $field_name = DOMAIN_ACCESS_FIELD) {
    static $domains;
    static $domain_roles;
    if (empty($domains)) {
      $domain_roles = [];
      $domains = \Drupal::entityTypeManager()->getStorage('domain')->loadByProperties();
      foreach ($domains as $domain) {
        $config = \Drupal::configFactory()->get('domain.roles.' . $domain->getOriginalId());
        $roles = $config->get('roles');
        if ($roles) {
          foreach ($roles as $role_id) {
            if (empty($domain_roles[$role_id])) {
              $domain_roles[$role_id] = [];
            }
            $domain_roles[$role_id][$domain->id()] = $domain->getDomainId();
          }
        }
      }
    }

    $ret = parent::getAccessValues($entity, $field_name);
    if (is_null($entity)) {
      return $ret;
    }

    if ($entity instanceof User) {
      foreach ($entity->getRoles() as $role_id) {
        if (isset($domain_roles[$role_id])) {
          $ret += $domain_roles[$role_id];
        }
      }
    }

    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public function checkEntityAccess(FieldableEntityInterface $entity, AccountInterface $account) {
    return $this->parent->checkEntityAccess($entity, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function hasDomainPermissions(AccountInterface $account, DomainInterface $domain, array $permissions, $conjunction = 'AND') {
    return $this->parent->hasDomainPermissions($account, $domain, $permissions, $conjunction);
  }

}
