<?php

/**
 * @file
 * Contains Drupal\xai\Form\SettingsForm.
 */

namespace Drupal\xai\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\xai\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'xai.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('xai.settings');
    $form['proposal'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Requested Feature Emails'),
      '#default_value' => $config->get('proposal'),
    );
	$form['demo'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Demo Emails'),
      '#default_value' => $config->get('demo'),
    );
	$form['poc'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('POC Support Emails'),
      '#default_value' => $config->get('poc'),
    );
	
	 $form['domain_msp_config'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Domain MSP Config'),
      '#default_value' => $config->get('domain_msp_config'),
    );
	
	 $form['submit_query'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Query Submit Emails'),
      '#default_value' => $config->get('submit_query'),
    );
	
	
	
	//config env urls
	
	$form['dev_env'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Dev environment url'),
      '#default_value' => $config->get('dev_env'),
	  '#maxlength' => 1024,
    );
	
	$form['uat_env'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Uat environment url'),
      '#default_value' => $config->get('uat_env'),
	  '#maxlength' => 1024,
    );
	
	$form['prod_env'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Prod environment url'),
      '#default_value' => $config->get('prod_env'),
	  '#maxlength' => 1024,
    );
	
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
   
    $this->config('xai.settings')
      ->set('proposal', $form_state->getValue('proposal'))
	  ->set('submit_query', $form_state->getValue('submit_query'))
      ->set('demo', $form_state->getValue('demo'))
      ->set('poc', $form_state->getValue('poc'))
	  ->set('domain_msp_config', $form_state->getValue('domain_msp_config'))
      ->set('prod_env', $form_state->getValue('prod_env'))
      ->set('uat_env', $form_state->getValue('uat_env'))
      ->set('dev_env', $form_state->getValue('dev_env'))
      ->save();
  }

}
