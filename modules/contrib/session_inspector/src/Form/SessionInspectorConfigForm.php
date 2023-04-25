<?php

namespace Drupal\session_inspector\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\session_inspector\Plugin\BrowserFormatManager;
use Drupal\session_inspector\Plugin\HostnameFormatManager;

/**
 * A form for configuring the session inspector module.
 *
 * @package Drupal\session_inspector\Form
 */
class SessionInspectorConfigForm extends ConfigFormBase {

  /**
   * The browser format plugin manager.
   *
   * @var \Drupal\session_inspector\Plugin\BrowserFormatManager
   */
  protected $browserFormatManager;

  /**
   * The hostname format plugin manager.
   *
   * @var \Drupal\session_inspector\Plugin\HostnameFormatManager
   */
  protected $hostnameFormatManager;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The date format entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $dateFormatStorage;

  /**
   * The date time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $dateTime;

  /**
   * Constructs a SessionInspectorConfigForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\session_inspector\Plugin\BrowserFormatManager $browser_format_manager
   *   The browser format plugin manager.
   * @param \Drupal\session_inspector\Plugin\HostnameFormatManager $hostname_format_manager
   *   The hostname format plugin manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $date_format_storage
   *   The date format storage.
   * @param \Drupal\Component\Datetime\TimeInterface $date_time
   *   The date time service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, BrowserFormatManager $browser_format_manager, HostnameFormatManager $hostname_format_manager, DateFormatterInterface $date_formatter, EntityStorageInterface $date_format_storage, TimeInterface $date_time) {
    parent::__construct($config_factory);

    $this->browserFormatManager = $browser_format_manager;
    $this->hostnameFormatManager = $hostname_format_manager;

    $this->dateFormatter = $date_formatter;
    $this->dateFormatStorage = $date_format_storage;
    $this->dateTime = $date_time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.session_inspector.browser_format'),
      $container->get('plugin.manager.session_inspector.hostname_format'),
      $container->get('date.formatter'),
      $container->get('entity_type.manager')->getStorage('date_format'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'session_inspector.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'session_inspector_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('session_inspector.settings');

    $form['description'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('Settings and options for the Session Inspector module.') . '</p>',
    ];

    // Set up the browser format plugin options.
    $browserFormatPluginDefinitions = $this->browserFormatManager->getDefinitions();

    $browserFormatOptions = [];
    foreach ($browserFormatPluginDefinitions as $pluginId => $pluginDefinition) {
      /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $pluginDefinition */
      $pluginDefinitionName = $pluginDefinition['name'];
      $browserFormatOptions[$pluginId] = $pluginDefinitionName->render() . ' (' . $pluginId . ')';
    }

    $form['browser_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Browser format'),
      '#default_value' => $config->get('browser_format') ?? 'basic',
      '#options' => $browserFormatOptions,
      '#description' => $this->t('Select the browser format plugin.'),
    ];

    // Set up the hostname format plugin options.
    $hostnameFormatPluginDefinitions = $this->hostnameFormatManager->getDefinitions();

    $hostnameFormatOptions = [];
    foreach ($hostnameFormatPluginDefinitions as $pluginId => $pluginDefinition) {
      /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $pluginDefinition */
      $pluginDefinitionName = $pluginDefinition['name'];
      $hostnameFormatOptions[$pluginId] = $pluginDefinitionName->render() . ' (' . $pluginId . ')';
    }

    $form['hostname_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Hostname format'),
      '#default_value' => $config->get('hostname_format') ?? 'basic',
      '#options' => $hostnameFormatOptions,
      '#description' => $this->t('Select the hostname format plugin.'),
    ];

    $date_formats = [];

    foreach ($this->dateFormatStorage->loadMultiple() as $machine_name => $value) {
      $formatArguments = [
        '@name' => $value->label(),
        '@date' => $this->dateFormatter->format($this->dateTime->getRequestTime(), $machine_name),
      ];
      $date_formats[$machine_name] = $this->t('@name format: @date', $formatArguments);
    }

    $date_formats['custom'] = $this->t('Custom');

    $form['date_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Date format'),
      '#options' => $date_formats,
      '#default_value' => $config->get('date_format') ?? 'medium',
      '#description' => $this->t('Select the format of the date.'),
    ];

    $form['date_interval_include'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include date interval?'),
      '#default_value' => $config->get('date_interval_include') ?? TRUE,
      '#description' => $this->t('Include a "ago" readout with the date.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('session_inspector.settings')
      ->set('hostname_format', $form_state->getValue('hostname_format'))
      ->set('browser_format', $form_state->getValue('browser_format'))
      ->set('date_format', $form_state->getValue('date_format'))
      ->set('date_interval_include', $form_state->getValue('date_interval_include'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
