<?php

namespace Drupal\news\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\contentservice\GenericService;

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
	         $start_time =$_GET['start_time'];
		     $end_time =$_GET['end_time'];
    //Get Domain from headers - 
	$client_id = \Drupal::request()->headers->get('Client-Id');
	/** @var \Drupal\contentservice\Service\GenericService $service */
	$service = \Drupal::service('contentservice.GenericService');
	$domain_id = $service->getDomainIdFromClientId($client_id);		 
	if ((!empty($start_time)) && (!empty($end_time))) {
    $entities = \Drupal::entityQuery('node')
				->condition('status', 1)
				->condition('type', 'event_calendar')
                ->condition('field_domain_access' ,$domain_id)
	            ->condition('field_event_date', $start_time, '>=')   
				->condition('field_event_date', $end_time, '<=')   
				->execute();
	} else {
		$entities = \Drupal::entityQuery('node')
				->condition('status', 1)
                ->condition('field_domain_access' ,$domain_id)
				->condition('type', 'event_calendar')
	         	->execute();
		
	}
    if(!empty($entities)){
        $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entities);
        foreach ($nodes as $key => $entity) {
        $result['data'][] = [
				"id"=>  $entity->id(),
				"title"=>  $entity->title->value,
				'type'=> $entity->getType(),
			    "body"=>  $entity->body->value,
                'field_event_category' =>$entity->field_event_category->value,
                'field_event_color_code' =>$entity->field_event_color_code->value,
                'field_event_date' =>$entity->field_event_date->value,
                'field_event_location' =>$entity->field_event_location->value
               ];
		}
     
 	}
	 if(empty($result)) {$result = [];}
    return new JsonResponse($result);

  }

}

