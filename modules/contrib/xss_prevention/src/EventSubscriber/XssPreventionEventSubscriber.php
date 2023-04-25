<?php

namespace Drupal\xss_prevention\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\xss_prevention\Form\XssPreventionConfigForm;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Event Subscriber.
 */
class XssPreventionEventSubscriber implements EventSubscriberInterface {

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Handle module configuration.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * XssPreventionEventSubscriber constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Configuration service.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory) {
    $this->logger = $logger_factory;
    $this->config = $config_factory->getEditable('xss_prevention.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['kernel.request'] = ['xssChecker'];

    return $events;
  }

  /**
   * This method is called when the kernel.request is dispatched.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The dispatched event.
   */
  public function xssChecker(Event $event) {
    if ($this->config->get('xss_prevention_enable') ?? NULL) {

      if ($this->config->get('xss_prevention_routes_white_list') ?? NULL) {
        $excludedRoutes = array_map('trim', explode("\n", $this->config->get('xss_prevention_routes_white_list')));

        $currentRoute = \Drupal::routeMatch()->getRouteName();
        foreach ($excludedRoutes as $route) {
          if ($currentRoute === $route) {
            return;
          }
        }
      }

      /** @var \Symfony\Component\HttpFoundation\Request $request */
      $request = $event->getRequest();

      $request->getRequestUri();
      $url = strtolower($request->getRequestUri());

      $checkedCharacters = $this->config->get('xss_prevention_characters') ?? XssPreventionConfigForm::XSS_PREVENTION_DEFAULT_CHARACTERS;
      $checkedJsEvents = $this->config->get('xss_prevention_js_events') ?? XssPreventionConfigForm::XSS_PREVENTION_DEFAULT_JS_EVENTS;

      $xss_checks = explode(',', $checkedCharacters . ',' . $checkedJsEvents);

      foreach ($xss_checks as $xss) {
        if (strpos($url, $xss) > 0) {
          $this->logger->get('XSS Prevention')->warning('XSS attack attempt in ' . $url . ' : ' . $xss . ' found.');
          $response = new RedirectResponse('/');
          $event->setResponse($response);
        }
      }
    }
  }

}
