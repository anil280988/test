<?php

namespace Drupal\session_inspector\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\session_inspector\Plugin\BrowserFormatManager;
use Drupal\session_inspector\Plugin\HostnameFormatManager;
use Drupal\session_inspector\SessionInspectorInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;

/**
 * Controller for inspecting and managing the sessions available for a user.
 *
 * @package Drupal\session_inspector\Controller
 */
class UserSessionInspector extends ControllerBase {

  /**
   * The config name.
   *
   * @var string
   */
  protected $configName = 'session_inspector.settings';

  /**
   * The SessionInspector service.
   *
   * @var \Drupal\session_inspector\SessionInspectorInterface
   */
  protected $sessionInspector;

  /**
   * The config factory object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

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
   * Constructs a UserSessionInspector object.
   *
   * @param \Drupal\session_inspector\SessionInspectorInterface $session_inspector
   *   The SessionInspector service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\session_inspector\Plugin\BrowserFormatManager $browser_format_manager
   *   The browser format manager.
   * @param \Drupal\session_inspector\Plugin\HostnameFormatManager $hostname_format_manager
   *   The hostname format manager.
   */
  public function __construct(SessionInspectorInterface $session_inspector, ConfigFactoryInterface $config_factory, DateFormatterInterface $date_formatter, BrowserFormatManager $browser_format_manager, HostnameFormatManager $hostname_format_manager) {
    $this->sessionInspector = $session_inspector;
    $this->configFactory = $config_factory;
    $this->dateFormatter = $date_formatter;
    $this->browserFormatManager = $browser_format_manager;
    $this->hostnameFormatManager = $hostname_format_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('session_inspector'),
      $container->get('config.factory'),
      $container->get('date.formatter'),
      $container->get('plugin.manager.session_inspector.browser_format'),
      $container->get('plugin.manager.session_inspector.hostname_format')
    );
  }

  /**
   * Callback for the route 'session_inspector.manage'.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user, as discerned from the path.
   *
   * @return array
   *   The renderable array for the page output.
   */
  public function inspectSessionPage(UserInterface $user) {
    $build = [];

    $config = $this->configFactory->get($this->configName);

    $sessions = $this->sessionInspector->getSessions($user);

    $rows = [];

    if (count($sessions) == 0) {
      // No session data found.
      $rows[] = [
        [
          'data' => $this->t('No session data found.'),
          'colspan' => 5,
        ],
      ];
    }
    else {
      $hostnameFormat = $config->get('hostname_format');
      $browserFormat = $config->get('browser_format');

      $hostnameFormatter = $this->hostnameFormatManager->createInstance($hostnameFormat);
      $browserFormatter = $this->browserFormatManager->createInstance($browserFormat);

      $dateIntervalInclude = $config->get('date_interval_include') ?? TRUE;
      $dateFormat = $config->get('date_format') ?? 'medium';

      foreach ($sessions as $i => $session) {
        $rows[] = [
          $this->sessionInspector->isCurrentSession($session['sid']) ? $this->t('YES') : '',
          $hostnameFormatter->formatHostname($session['hostname']),
          $this->formatTimestamp($session['timestamp'], $dateFormat, $dateIntervalInclude),
          $browserFormatter->formatBrowser($session['browser']),
          [
            'data' => $this->formatDeleteLink($user, $session['sid']),
            'data-test' => ['session-operation-' . $i],
          ],
        ];
      }
    }

    $sessionsTable = [
      '#type' => 'table',
      '#header' => [
        $this->t('Current'),
        $this->t('Location'),
        $this->t('Timestamp'),
        $this->t('Browser'),
        $this->t('Operations'),
      ],
      '#rows' => $rows,
      '#attributes' => [
        'class' => [
          'sessions-table',
        ],
      ],
    ];

    $build['session_table'] = [
      '#theme' => 'sessions',
      '#description' => $this->t('Here is a list of sessions registered to your user account'),
      '#sessions' => $sessionsTable,
    ];

    if (count($sessions) > 1) {
      $build['delete_all_sessions_link'] = [
        '#type' => 'link',
        '#url' => Url::fromRoute('session_inspector.delete_all', ['user' => $user->id()]),
        '#title' => $this->t('Delete all other sessions'),
        '#attributes' => [
          'class' => [
            'button',
            'button--danger',
          ],
          'data-test' => [
            'session-operation-delete-all',
          ],
        ],
      ];
    }

    return $build;
  }

  /**
   * Format a timestamp to a format.
   *
   * @param int $timestamp
   *   The timestamp value.
   * @param string $dateFormat
   *   The date format to apply.
   * @param bool $dateIntervalInclude
   *   Option to include the date interval output.
   *
   * @return string
   *   The formatted timestamp.
   */
  public function formatTimestamp(int $timestamp, string $dateFormat, bool $dateIntervalInclude):string {
    $dateString = $this->dateFormatter->format($timestamp, $dateFormat);

    if ($dateIntervalInclude === TRUE) {
      $date = new \DateTime();
      $date->setTimestamp($timestamp);
      $timeInterval = $this->dateFormatter->formatInterval(time() - $timestamp, 1);

      $dateString .= ' (' . $timeInterval . ' ' . $this->t('ago') . ')';
    }

    return $dateString;
  }

  /**
   * Create a delete link.
   *
   * Given a user object and a session ID, create a delete link for the
   * sessions table.
   *
   * @param \Drupal\user\UserInterface $user
   *   The current user.
   * @param string $sessionId
   *   The session ID.
   *
   * @return array
   *   The link as a render array.
   */
  public function formatDeleteLink(UserInterface $user, string $sessionId) {
    $options = ['user' => $user->id(), 'sid' => $sessionId];
    $linkObject = Link::createFromRoute('Delete', 'session_inspector.delete', $options);
    return $linkObject->toRenderable();
  }

}
