<?php

namespace Drupal\contentservice\Plugin\rest\resource;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Drupal\Core\Session\AccountProxyInterface;
use \Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\contentservice\GenericService;
use Drupal\jwt\Transcoder\JwtTranscoder;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\domain\DomainInterface;
use Drupal\rest\ResourceResponse;
use Drupal\taxonomy\Entity\Term;
use \Drupal\file\Entity\File;
use Psr\Log\LoggerInterface;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\user\Entity\Role;
use \Firebase\JWT\JWT;
use Drupal\Core\Url;

/**
 * Provides a resource to get view modes by entity and bundle. 
 *
 * @RestResource(
 *   id = "concierto_cs_show_tutorials",
 *   label = @Translation("concierto cs show tutorials"),
 *  serialization_class = "",
 *   uri_paths = {
 *      "canonical" = "/api/csShowTutorials",
 *   }
 * )
 */
class csShowTutorials extends ResourceBase {

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
	array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, AccountProxyInterface $current_user) {
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
   * @return \Drupal\rest\ResourceResponse Throws exception expected.
   * Throws exception expected.
   */
  public function get() {
	/** @var \Drupal\contentservice\Service\GenericService $service */
	$service = \Drupal::service('contentservice.GenericService');
	$login = $service->userDuplicateLoginValidation();
	if($login!='1'){
	  $message="Invalid Login";
	  throw new AccessDeniedHttpException($message);
	}
	//Get Domain from header - 
	$client_id = \Drupal::request()->headers->get('Client-Id');
	$domain_id = $service->getDomainIdFromClientId($client_id);
	$queryString = \Drupal::requestStack()->getCurrentRequest()->query->all();
	$query = \Drupal::entityQuery('node');
	$query->condition('status', 1);
	$query->condition('type', 'tutorials');
	$query->condition('langcode', 'en');
	$query->condition('field_domain_access' ,$domain_id);
	if(!empty($queryString['category']) || isset($queryString['category'])) { 
	  $query->condition('field_tutorial_category', $queryString['category']);
	  $query->range(0, $queryString['limit']);
	}
	$query->sort('created', 'DESC');
	$query->accessCheck(false);
	$entities = $query->execute();

    if(!empty($entities)){
	  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entities);
	  foreach ($nodes as $key => $entity) {
		$idd = $entity->get('uid')->getValue()[0]['target_id'];
		$user = \Drupal\user\Entity\User::load($idd);
		if(!empty($entity->get('field_tutorial_banner')->getValue()[0]['target_id'])){
		  $file = File::load($entity->get('field_tutorial_banner')->getValue()[0]['target_id']);
		  $imageurl = file_create_url($file->getFileUri());
		}
	    $result[] = [
		  "id"=> $entity->id(),	
		  "uuid"=> $entity->uuid(),
		  "title"=> $entity->get('title')->getValue()[0]['value'],
		  "trending_tutorials"=> $entity->get('field_trending_tutorials')->getValue()[0]['value'],
		  "image"=> $imageurl,
		  "category"=> $entity->get('field_tutorial_category')->getValue()[0]['value'],
		  "credits"=> $entity->get('field_tutorial_credits')->getValue()[0]['value'],
		  "link" => $entity->get('field_tutorial_link')->getValue()[0]['value'],
		  "mode"=> $entity->get('field_tutorial_mode')->getValue()[0]['value'],
		  "time"=> $entity->get('field_tutorial_time')->getValue()[0]['value'],
		  "type"=> $entity->get('field_tutorial_type')->getValue()[0]['value'],			
		  "created"=> date('Y-m-d H:i:s',$entity->get('created')->getValue()[0]['value']),
		  "author"=> $user->get('name')->getValue()[0]['value'],
		];
	  }
 	}
    if(empty($result)) {
	  $result = [];
    }
	
	$response = new ResourceResponse($result);
    $response->addCacheableDependency($result);
    return $response;
	
  }
	
}