<?php

namespace Drupal\contentservice\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Psr\Log\LoggerInterface;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\user\Entity\Role;
use Drupal\taxonomy\Entity\Term;
use \Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\jwt\Transcoder\JwtTranscoder;
use \Firebase\JWT\JWT;
use \Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\domain\DomainInterface;
use Drupal\contentservice\GenericService; 

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
                $configuration, $plugin_id, $plugin_definition, $container->getParameter('serializer.formats'), $container->get('logger.factory')->get('plusapi'), $container->get('current_user')
        );
    }

    /**
     * Responds to GET requests.
     *
     * Returns a list of bundles for specified entity.
     *
     * @param $node_type
     * @param $data
     * @return \Drupal\rest\ResourceResponse Throws exception expected.
     * Throws exception expected.
     */
   public function get() {
	   //Get Domain from header - 
		$client_id = \Drupal::request()->headers->get('Client-Id');
		/** @var \Drupal\contentservice\Service\GenericService $service */
		$service = \Drupal::service('contentservice.GenericService');
		$login = $service->userDuplicateLoginValidation();
		if($login!='1'){
			$message="Invalid Login";
			throw new AccessDeniedHttpException($message);
		}
		$domain_id = $service->getDomainIdFromClientId($client_id);
		$queryString = \Drupal::requestStack()->getCurrentRequest()->query->all();
        if(empty($queryString['category']) || !isset($queryString['category'])) { 
		$entities = \Drupal::entityQuery('node')
        ->condition('status', 1)
        ->condition('type', 'tutorials')
        ->condition('langcode', 'en')
		
		->condition('field_domain_access' ,$domain_id)
		->sort('created', 'DESC')
        ->accessCheck(false)
        ->execute();
		} 
		else {
			$entities = \Drupal::entityQuery('node')
			->condition('status', 1)
			->condition('type', 'tutorials')
			->condition('langcode', 'en')
			->condition('field_tutorial_category', $queryString['category'])
			->condition('field_domain_access' ,$domain_id)
			->range(0, $queryString['limit'])
			->sort('created', 'DESC')
			->accessCheck(false)
			->execute();
		}

    if(!empty($entities)){
		$nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entities);
	    foreach ($nodes as $key => $entity) {

		$user = \Drupal\user\Entity\User::load($entity->uid->target_id);
		$idd= $entity->uid->target_id;
		$database = \Drupal::database();
		$nidread= $entity->id();
	    $query = $database->query("SELECT id FROM broadcast_users where nid=$nidread AND uid=$idd");
        $results = $query->fetchAll();
        $readcount = count($results);
		
		if(!empty($entity->field_tutorial_banner->target_id)){
			$file = File::load($entity->field_tutorial_banner->target_id);
			$url= $file->uri->value;
			 $urls=file_create_url($url);
			}
		
        $result[] = [
				"id"=>  $entity->id(),	
				"uuid"=> $entity->uuid(),
				"title"=>  $entity->title->value,
				"trending_tutorials"=>  $entity->field_trending_tutorials->value,
				"image"=>  $urls,
				"category"=>  $entity->field_tutorial_category->value,
				"credits"=>  $entity->field_tutorial_credits->value,
				"link" => $entity->field_tutorial_link->value,
				"mode"=>  $entity->field_tutorial_mode->value,
				"time"=>  $entity->field_tutorial_time->value,
				"type"=>  $entity->field_tutorial_type->value,			
				"created"=>  date('Y-m-d H:i:s',$entity->created->value),
				"author"=>  $user->name->value,
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