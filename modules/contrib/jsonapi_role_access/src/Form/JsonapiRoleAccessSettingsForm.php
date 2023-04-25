<?php

namespace Drupal\jsonapi_role_access\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure JSON:API user role access settings for this site.
 */
class JsonapiRoleAccessSettingsForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'jsonapi_role_access.settings';

  /**
   * The entityTypeManager object.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entityTypeManager objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManager $entityTypeManager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jsonapi_role_access_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);
    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
    $rolesOptions = [];
    foreach ($roles as $role) {
      if ($role->id() != 'administrator') {
        $rolesOptions[$role->id()] = $role->label();
      }
    }
    $form['negate'] = [
      '#type' => 'radios',
      '#options' => [
        1 => $this->t('Allow the selected below'),
        0 => $this->t('Restrict the selected below'),
      ],
      '#title' => $this->t('Which user type should be allowed?'),
      '#default_value' => $config->get('negate'),
      '#required' => TRUE,
    ];
    $form['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('User roles'),
      '#description' => $this->t('Select roles for which you want to allow or restrict access to jsonapi resources, (Administrator role has all permissions).'),
      '#multiple' => TRUE,
      '#options' => $rolesOptions,
      '#required' => TRUE,
      '#default_value' => $config->get('roles'),
    ];
    $form['#attached']['library'][] = 'jsonapi_role_access/jsonapi_role_access.authenticated';
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config(static::SETTINGS);
    foreach ($form_state->cleanValues()->getValues() as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

}
