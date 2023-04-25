<?php

namespace Drupal\domain_role\Plugin\views\filter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\user\Plugin\views\filter\Roles;
use Drupal\user\RoleStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Filters for users with roles in this domain.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("domain_roles")
 */
class DomainRoles extends Roles {

  /**
   * @var DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  /**
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  public function __construct(array $configuration, string $plugin_id,
    $plugin_definition,
    RoleStorageInterface $role_storage,
    DomainNegotiatorInterface $domainNegotiator,
    ConfigFactoryInterface $configFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $role_storage);
    $this->domainNegotiator = $domainNegotiator;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('user_role'),
      $container->get('domain.negotiator'),
      $container->get('config.factory')
    );
  }

  /**
   * Override query to change the value to the domain-specific version.
   */
  public function query() {
    foreach ($this->value as $key => $value) {
      $this->value[$key] = _domain_role_role_id($value);
    }
    parent::query();
  }

}
