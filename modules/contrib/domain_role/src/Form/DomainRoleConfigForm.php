<?php

namespace Drupal\domain_role\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class DomainRoleConfigForm.
 */
class DomainRoleConfigForm extends ConfigFormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  /**
   * Constructs a new DomainRoleConfigForm object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
      EntityTypeManagerInterface $entity_type_manager
    ) {
    parent::__construct($config_factory);
        $this->entityTypeManager = $entity_type_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'domain_role.roles',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'domain_role_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $roles_storage = $this->entityTypeManager->getStorage('user_role');
    $all_roles = $roles_storage->loadMultiple();
    // No configuration entities for authenticated or anonymous users, so unset those.
    unset($all_roles[RoleInterface::ANONYMOUS_ID]);
    unset($all_roles[RoleInterface::AUTHENTICATED_ID]);
    $all_roles = array_map(function ($item) {
      return $item->label();
    }, $all_roles);

    $config = $this->config('domain_role.roles');
    $form['domain_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles to apply per domain'),
      '#description' => $this->t('Select roles that should be applied per domain.'),
      '#options' => $all_roles,
      '#default_value' => $config->get('domain_roles'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $domain_roles = array_filter($form_state->getValue('domain_roles'));

    $this->config('domain_role.roles')
      ->set('domain_roles', $domain_roles)
      ->save();

    $domains = $this->entityTypeManager->getStorage('domain')->loadMultiple();
    foreach($domain_roles as $domain_role) {
      foreach($domains as $domain_id => $domain) {
        $config = $this->configFactory()
          ->getEditable('domain.config.' . $domain_id . '.user.role.' . $domain_role);
        $config->set('name', $domain_id . '-' . $domain_role)
          ->set('label', $domain->label() . ' '. $domain_role)
          ->set('id', $domain_role)
          ->save();
      }
    }
  }

}
