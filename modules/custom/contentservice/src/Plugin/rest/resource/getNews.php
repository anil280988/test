<?php

namespace Drupal\contentservice\Plugin\rest\resource;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\contentservice\GenericService;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Drupal\file\Entity\File;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "get_news_rest_resource",
 *   label = @Translation("get_news_rest_resource"),
 *   uri_paths = {
 *     "canonical" = "/api/getNews"
 *   }
 * )
 */

class getNews extends ResourceBase {

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
	/** @var \Drupal\contentservice\Service\GenericService $service */
	$service = \Drupal::service('contentservice.GenericService');
	//Set Domain - 
	$client_id = \Drupal::request()->headers->get('Client-Id');
	$domain_id = $service->getDomainIdFromClientId($client_id);
	$login = $service->userDuplicateLoginValidation();
	if($login!='1'){
	  $message="Invalid Login";
	  throw new AccessDeniedHttpException($message);
	}
	if(empty($domain_id)) {
	  $message="Invalid MSP, Please contact Administrator";
	  throw new AccessDeniedHttpException($message);	
	}
	$hasClientpermission = $service->userClientPermissioCheck($client_id);
	if(empty($hasClientpermission)) {
	  $message="Client Access Deny, Kindly contact Site Admin";
	  throw new AccessDeniedHttpException($message);
	}
	$hasClientpermission = $service->userClientPermissioCheck($client_id);
	if(empty($hasClientpermission)) {
	  $message="Client Access Deny, Kindly contact Site Admin";
	  throw new AccessDeniedHttpException($message);
	}

	$query = \Drupal::entityQuery('node');
	$query->condition('status', 1);
	$query->condition('type', 'news');
	$query->condition('langcode','en');
	$query->condition('field_domain_access' ,$domain_id);
	$query->sort('nid' , 'DESC');
	$query->accessCheck(false);
	$entities = $query->execute();
	
    if(!empty($entities)){
	  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entities);
	  foreach ($nodes as $key => $entity) {
		if(!$entity->get('field_news_banner')->isEmpty()) {
		  $fid = $entity->get('field_news_banner')->getValue()[0]['target_id'];
		  $file = File::load($fid);
		  $url= $file->getFileUri();
		  $urls = $file->createFileUrl();
		}
		
	    $result['data'][] = [
		  "id"=> $entity->id(),
		  "user"=> $entity->get('uid')->getValue()[0]['target_id'],
		  "title"=> $entity->get('title')->getValue()[0]['value'],
		  "body"=> $entity->get('field_news_description')->getValue()[0]['value'],
		  "category"=> $entity->get('field_news_categories')->getValue()[0]['value'],
		  "banner"=> $entity->get('field_news_banner_url')->getValue()[0]['value'],
		  "trending"=> $entity->get('field_trending_news')->getValue()[0]['value'],
		  "tags"=> $entity->get('field_news_tags')->getValue()[0]['value'],
		  "date"=> $entity->get('changed')->getValue()[0]['value'],
		  "status"=> $entity->get('status')->getValue()[0]['value'],
		  "published"=> $entity->get('moderation_state')->getValue()[0]['value'],
		];
	  }
 	}
	$response = new ResourceResponse($result);
    $response->addCacheableDependency($result);
    return $response;
  }

}

