<?php

namespace Drupal\contentservice\Plugin\rest\resource;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\contentservice\GenericService;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "get_broadcast_rest_resource",
 *   label = @Translation("get_broadcast_rest_resource"),
 *   uri_paths = {
 *     "canonical" = "/api/getBroadcast/{id}"
 *   }
 * )
 */

class getBroadcast extends ResourceBase {

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
	$this->connection = \Drupal::database();
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
      $container->get('logger.factory')->get('broadcast'),
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
  public function get($id) {
	//Get Domain from headers - 
	$client_id = \Drupal::request()->headers->get('Client-Id');
	/** @var \Drupal\contentservice\Service\GenericService $service */
	$service = \Drupal::service('contentservice.GenericService');
	$login = $service->userDuplicateLoginValidation();
	if($login!='1'){
	  $message="Invalid Login";
	  throw new AccessDeniedHttpException($message);
	}
	$domain_id = $service->getDomainIdFromClientId($client_id);
    $query = \Drupal::entityQuery('node');
	$query->condition('status', 1);
	$query->condition('type', 'broadcast');
	$query->condition('langcode','en');
	$query->condition('field_domain_access' ,$domain_id);
	$query->sort('created', 'DESC');
	$query->accessCheck(false);
	$entities = $query->execute();

    if(!empty($entities)){
	  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entities);
	  foreach ($nodes as $key => $entity) {
		$nidread= $entity->id();
		$id = $entity->get('uid')->getValue()[0]['target_id'];
		$user = \Drupal\user\Entity\User::load($id);
		$query = \Drupal::database()->select('broadcast_users', 'bu');
		$query->addField('bu', 'id');
		$query->condition('bu.nid', $nidread);
		$query->condition('bu.uid', $id);
		$results = $query->execute()->fetchAll();
		$readcount = count($results);
		if($readcount>=1){
		  $read=1;
		} else {
		  $read=0;
		}
		$authorName = $user->get('name')->getValue()[0]['value'];
		if (stristr($authorName, '---')){
		  $authorNameArray=explode("---",$authorName);
		  $authorName = $authorNameArray[1];
		}
		$result['data'][] = [
		  "id"=>  $entity->id(),	
		  "uuid"=> $entity->uuid(),
		  "title"=>  $entity->get('title')->getValue()[0]['value'],
		  "created"=>  date('Y-m-d H:i:s',$entityget('created')->getValue()[0]['value']),
		  "author"=>  $authorName,
		  "read" =>$read,
		];
	  }
 	}
	if(empty($result)) {$result = [];}
	$response = new ResourceResponse($result);
    $response->addCacheableDependency($result);
	return $response;
  }

}

