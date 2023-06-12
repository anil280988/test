<?php

namespace Drupal\contentservice\Plugin\rest\resource;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\contentservice\GenericService;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;


/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "get_events_rest_resource",
 *   label = @Translation("get_events_rest_resource"),
 *   uri_paths = {
 *     "canonical" = "/api/getEvents"
 *   }
 * )
 */

class getEvents extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('article'),
      $container->get('current_user')
    );
  }

  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get() {
	//Get Domain from headers - 
	$client_id = \Drupal::request()->headers->get('Client-Id');
	/** @var \Drupal\contentservice\Service\GenericService $service */
	$service = \Drupal::service('contentservice.GenericService');
	$login = $service->userDuplicateLoginValidation();
	if($login!='1'){
	  $message="Invalid Login";
	  throw new AccessDeniedHttpException($message);
	}
	$queryString = \Drupal::requestStack()->getCurrentRequest()->query->all();
	$start_time =$queryString['start_time'];
	$end_time =$queryString['end_time'];
	$domain_id = $service->getDomainIdFromClientId($client_id); 	
	$query = \Drupal::entityQuery('node');
	$query->condition('status', 1);
	$query->condition('type', 'event_calendar');
	$query->condition('field_domain_access' ,$domain_id);
	if ((!empty($start_time)) && (!empty($end_time))) {
		$query->condition('field_event_date', $start_time, '>=');
		$query->condition('field_event_date', $end_time, '<=');
	}
	$entities = $query->execute();
	
    if(!empty($entities)) {
      $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entities);
      foreach ($nodes as $key => $entity) {
        $result['data'][] = [
		  "id" => $entity->id(),
		  "title" => $entity->get('title')->getValue()[0]['value'],
		  "type" => $entity->getType(),
		  "body" => $entity->get('body')->getValue()[0]['value'],
		  "field_event_category" => $entity->get('field_event_category')->getValue()[0]['value'],
		  "field_event_color_code" => $entity->get('field_event_color_code')->getValue()[0]['value'],
		  "field_event_date" => $entity->get('field_event_date')->getValue()[0]['value'],
		  "field_event_location" => $entity->get('field_event_location')->getValue()[0]['value'],
        ];
	  }
 	}
	if(empty($result)) {$result = [];}
    return new JsonResponse($result);

  }

}

