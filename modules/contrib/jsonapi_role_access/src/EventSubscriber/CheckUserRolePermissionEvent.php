<?php

namespace Drupal\jsonapi_role_access\EventSubscriber;

use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Checks subscriptions for user role access for json api API keys.
 */
class CheckUserRolePermissionEvent implements EventSubscriberInterface {

  /**
   * The config object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Constructs this factory object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   The account object.
   */
  public function __construct(ConfigFactoryInterface $configFactory, AccountProxy $currentUser) {
    $this->config = $configFactory->get('jsonapi_role_access.settings');
    $this->currentUser = $currentUser;
  }

  /**
   * Returns an array of event names this subscriber wants to listen to.
   *
   * The array keys are event names and the value can be:
   *
   *  * The method name to call (priority defaults to 0)
   *  * An array composed of the method name to call and the priority
   *  * An array of arrays composed of the method names to call and respective
   *    priorities, or 0 if unset
   *
   * For instance:
   *
   *  * ['eventName' => 'methodName']
   *  * ['eventName' => ['methodName', $priority]]
   *  * ['eventName' => [['methodName1', $priority], ['methodName2']]]
   *
   * @return array
   *   The event names to listen to.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkUserRoleAccess', 30];
    return $events;
  }

  /**
   * Check if user has subscription, if not redirect to subscription page.
   */
  public function checkUserRoleAccess(Event $event) {
    if (!$event->getRequest()
      ->isXmlHttpRequest() && $this->strStartsWith($event->getRequest()
        ->get('_route'), 'jsonapi') && !in_array($event->getRequest()
        ->get('_route'), [
          'jsonapi.settings',
          'jsonapi_extras.settings',
          'jsonapi_role_access.config',
        ]
      )) {
      $actionType = $this->config->get('negate');
      $roles = array_filter($this->config->get('roles'));
      $userRoles = $this->currentUser->getRoles();
      if (isset($actionType) && !empty($roles) && !in_array('administrator', $userRoles)) {
        if (($actionType && empty(array_intersect($roles, $userRoles))) || (!$actionType && !empty(array_intersect($roles, $userRoles)))) {
          throw new AccessDeniedHttpException();
        }
      }

    }
  }

  /**
   * Polyfill for PHP 7.0 and later.
   *
   * @param string $haystack
   *   The source string.
   * @param string $needle
   *   The needle string.
   *
   * @return bool
   *   Return true if found else false.
   */
  private function strStartsWith($haystack, $needle) {
    if (function_exists('str_starts_with')) {
      return str_starts_with($haystack, $needle);
    }
    return \strncmp($haystack, $needle, \strlen($needle)) === 0;
  }

}
