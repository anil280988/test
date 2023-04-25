<?php

namespace Drupal\rest_log\EventSubscriber;

use Drupal\rest\ResourceResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class DefaultSubscriber.
 */
class RestLogSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a new DefaultSubscriber object.
   */
  public function __construct() {

  }

  /**
   * The key used in the form.
   *
   * @param string $resource_id
   *   The resource ID.
   *
   * @return string
   *   The resource key in the form.
   */
  protected function getResourceKey($resource_id) {
    return str_replace(':', '.', $resource_id);
  }

  /**
   * @param $request
   *
   * @return bool
   */
  public function isRestPage($request) {
    $resourcePluginManagerConfig = \Drupal::service('entity_type.manager')->getStorage('rest_resource_config');
    $config = $resourcePluginManagerConfig->loadMultiple();

    $resources = \Drupal::service('plugin.manager.rest')->getDefinitions();
    $allPaths = [];
    foreach ($resources as $id => $resource) {
      $key = $this->getResourceKey($id);
      if ($config[$key] && $config[$key]->status()) {
        $allPaths[] = $resource['uri_paths']['canonical'];
      }
    }

    $flag = FALSE;

    foreach ($allPaths as $allPath) {
      if (preg_match('#\{(.*)\}#', $allPath, $matchs)) {
        if (\Drupal::routeMatch()->getParameter($matchs[1])) {
          $flag =  TRUE;
        }
      } else {
        if (in_array($request->getPathInfo(), $allPaths)) {
          $flag = TRUE;
        }
      }
    }

    return $flag;
  }


  public function restLogResponse(FilterResponseEvent $event) {
    $request = $event->getRequest();
    $response = $event->getResponse();
    if ($this->isRestPage($request)) {
      $headers = $request->headers->all();
      foreach($headers as $headerKey => $header) {
        if (is_array($header) && count($header) === 1) {
          $headers[$headerKey] = $header[0];
        }
      }

      $responseBody = json_decode($response->getContent(), TRUE);
      $getExceptionError = $response->getStatusCode() === 500 ? drupal_static('rest_exception') : '';
      if ($getExceptionError) {
        $responseBody['Exception'] = $getExceptionError;
      }

      $responseLog = [
        'request_header' =>   print_r($headers, 1),
        'request_method' => $request->getMethod(),
        'request_payload' => $request->getContent(),
        'response_status' => $response->getStatusCode(),
        'response_body' => json_encode($responseBody),
        'request_uri' => $request->getUri(),
        'request_cookie' => print_r($request->cookies->all(), 1),
      ];

      \Drupal::entityTypeManager()->getStorage('rest_log')->create($responseLog)->save();
    }
  }

  /**
   * Exception.
   */
  public function onException($event) {
    $request = $event->getRequest();
    if ($this->isRestPage($request)) {
      $exception = $event->getException();
      drupal_static('rest_exception' , $exception->getMessage());
      $errorResponse = [
        'status' => 'error',
        'message' => t('System error, please contact the administrator'),
      ];
      if ($request->get('nolog')) {
        $errorResponse['message'] = $exception->getMessage();
      }
      $response = new ResourceResponse($errorResponse);
      $event->setResponse($response);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[KernelEvents::RESPONSE][] = ['restLogResponse'];
    $events[KernelEvents::EXCEPTION][] = ['onException', -254];
    return $events;
  }

}
