<?php

namespace Drupal\domain_role_access\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;

/**
 * Class DomainRolesForm.
 *
 * @package Drupal\domain_role_access\Form
 */
class DomainRolesForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'domain_role_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $build_info = $form_state->getBuildInfo();

    /** @var \Drupal\domain\DomainInterface $domain */
    $domain = $build_info['args'][0];

    $options = [];
    foreach (user_roles() as $key => $role) {
      /** @var Drupal\user\Entity\Role $role */
      $options[$key] = $role->label();
    }

    $config = $this->config('domain.roles.' . $domain->getOriginalId());

    $form = [
      'domain_id' => [
        '#type' => 'value',
        '#value' => $domain->getOriginalId(),
      ],
      'roles' => [
        '#type' => 'checkboxes',
        '#default_value' => array_keys($config->get('roles')),
        '#title' => $this->t('Roles'),
        '#options' => $options,
        '#description' => $this->t('These roles will have access to this domain.'),
      ],
      'actions' => [
        '#weight' => 20,
        '#type' => 'container',
        'submit' => [
          '#type' => 'submit',
          '#value' => $this->t('Submit'),
          '#button_type' => 'primary',
        ],
      ],
    ];

    $form['actions']['cancel'] = Link::createFromRoute($this->t('Cancel'), 'domain.admin')->toRenderable();
    $form['actions']['cancel']['#attributes']['class'] = ['button', 'button--danger'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $domain_id = $form_state->getValue('domain_id');
    $roles = array_filter($form_state->getValue('roles'));
    $config = $this->configFactory->getEditable('domain.roles.' . $domain_id);
    if (empty($roles)) {
      $config->delete();
    }
    else {
      $config->set('roles', $roles);
      $config->save();
    }
  }

}
