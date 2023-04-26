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
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\contentservice\GenericService;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "concierto_cs_show_productupdates",
 *   label = @Translation("concierto cs show product updates"),
 *  serialization_class = "",
 *   uri_paths = {
 *      "canonical" = "/api/csShowProductUpdates",
 *   }
 * )
 */
class csShowProductUpdates extends ResourceBase {

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
     * Responds to POST requests.
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
		$domain_id = $service->getDomainIdFromClientId($client_id);
		$entities = \Drupal::entityQuery('node')
        ->condition('status', 1)
        ->condition('type', 'product_updates')
        ->condition('langcode', 'en')
		//->condition('field_product_type', $type, '=')
        ->condition('field_domain_access' ,$domain_id)
        ->accessCheck(false)
		->sort('nid' , 'DESC')
        ->execute();
   
      if(!empty($entities)){
        $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entities);
		
        foreach ($nodes as $key => $entity) {
   
		if(!empty($entity->field_product_status->target_id)){	
		$status= \Drupal\taxonomy\Entity\Term::load($entity->field_product_status->target_id)->get('name')->value;
		$colorval= \Drupal\taxonomy\Entity\Term::load($entity->field_product_status->target_id)->get('description')->value;
		$colorval= strip_tags($colorval);
		$colorval= preg_replace("/[\n\r]/","",$colorval);
		} else {
			$colorval="";$status="";
		}
		
		$desc= strip_tags($entity->body->value);
		$descb= preg_replace("/[\n\r]/","",$desc);
		$result[]= [
				"id"=>  $entity->id(),	
				"uid"=> $entity->uid->target_id,
				"title"=>  $entity->title->value,
				"description"=>  $descb,
				"category" => $status,
				"color" => $colorval,
				"date"=>  date('Y-m-d',$entity->created->value),
				];
		   }
     
 	       }

      if(empty($result)) { $result=[];}
	  return new JsonResponse($result);
     
 	  }


}