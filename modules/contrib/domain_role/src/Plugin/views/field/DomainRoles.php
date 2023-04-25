<?php

namespace Drupal\domain_role\Plugin\views\field;

use Drupal\Core\Database\Connection;
use Drupal\user\Plugin\views\field\Roles;
use Drupal\views\Plugin\views\ViewsHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Field handler to show roles specific to a domain.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("domain_roles")
 */
class DomainRoles extends Roles {


  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $database);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('database')
    );
  }

  public function preRender(&$values) {
    $uids = [];
    $this->items = [];

    foreach ($values as $result) {
      $uids[] = $this->getValue($result);
    }

    if ($uids) {
      $roles = domain_role_user_roles();
      $result = $this->database->query('SELECT u.entity_id as uid, u.roles_target_id as rid FROM {user__roles} u WHERE u.entity_id IN ( :uids[] ) AND u.roles_target_id IN ( :rids[] )', [':uids[]' => $uids, ':rids[]' => array_keys($roles)]);
      foreach ($result as $role) {
        $this->items[$role->uid][$role->rid]['role'] = $roles[$role->rid]->label();
        $this->items[$role->uid][$role->rid]['rid'] = $role->rid;
      }
      // Sort the roles for each user by role weight.
      $ordered_roles = array_flip(array_keys($roles));
      foreach ($this->items as &$user_roles) {
        // Create an array of rids that the user has in the role weight order.
        $sorted_keys = array_intersect_key($ordered_roles, $user_roles);
        // Merge with the unsorted array of role information which has the
        // effect of sorting it.
        $user_roles = array_merge($sorted_keys, $user_roles);
      }
    }
  }
}
