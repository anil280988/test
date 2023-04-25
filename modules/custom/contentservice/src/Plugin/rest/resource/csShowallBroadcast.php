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
 *   id = "concierto_cs_show_all_broadcast",
 *   label = @Translation("concierto cs show all broadcast"),
 *  serialization_class = "",
 *   uri_paths = {
 *      "canonical" = "/api/csShowallBroadcast",
 *   }
 * )
 */
class csShowallBroadcast extends ResourceBase {

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
		$allses = \Drupal::database()->select('sessions', 's')
		  ->fields('s', ['uid', 'sid', 'hostname', 'timestamp'])
		  ->condition('s.uid', '1')
		  ->orderBy('s.timestamp', 'DESC')
		  ->execute()->fetchAll();
		 \Drupal::logger('all sessions of user')->warning('<pre><code>' . print_r($allses, TRUE) . '</code></pre>');
		
		$singlesess = \Drupal::database()->select('sessions', 's')
		  ->fields('s', ['sid'])
		  ->condition('s.uid', '1')
		  ->orderBy('s.timestamp', 'DESC')
		  ->execute()->fetchField();
		\Drupal::logger('current sessions of user')->warning('<pre><code>' . print_r($singlesess, TRUE) . '</code></pre>');
		
		
		
		$query = \Drupal::database()->delete('sessions')
			->condition('uid', 1)
			->execute();
  
  
		
		$afterses = \Drupal::database()->select('sessions', 's')
		  ->fields('s', ['uid', 'sid', 'hostname', 'timestamp'])
		  ->condition('s.uid', '1')
		  ->orderBy('s.timestamp', 'DESC')
		  ->execute()->fetchAll();
		 \Drupal::logger('sessions after')->warning('<pre><code>' . print_r($afterses, TRUE) . '</code></pre>');
		 
		 
		//\Drupal::logger('session log')->warning('<pre><code>' . print_r($sess, TRUE) . '</code></pre>');
		 
		//Get Domain from header - 
		$client_id = \Drupal::request()->headers->get('Client-Id');
		/** @var \Drupal\contentservice\Service\GenericService $service */
		$service = \Drupal::service('contentservice.GenericService');
		$domain_id = $service->getDomainIdFromClientId($client_id);		
		$entities = \Drupal::entityQuery('node')
				->condition('status', 1)
				->condition('type', 'broadcast')
				->condition('langcode','en')
				->condition('field_domain_access' ,$domain_id)
				->accessCheck(false)   
				->execute();
		
		if(!empty($entities)){
			$nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entities);
			
			foreach ($nodes as $key => $entity) {
				$user = \Drupal\user\Entity\User::load($entity->uid->target_id);
				$id= $entity->uid->target_id;
				$database = \Drupal::database();
				$nidread= $entity->id();
				$query = $database->query("SELECT id FROM broadcast_users where nid=$nidread AND uid=$id");
				$results = $query->fetchAll();
				$readcount = count($results);
				if($readcount>=1){$read=1;}else {$read=0;}
				$result['data'][] = [
					"id"=>  $entity->id(),	
					"uuid"=> $entity->uuid(),
					"title"=>  $entity->title->value,
					"group"=>  $entity->field_user_group->value,
					"created"=>  date('Y-m-d H:i:s',$entity->created->value),
					"author"=>  $user->name->value,
					"read" =>$read,
				];
			}
		}
		if(empty($result)) { $result=[];}
		$response = new ResourceResponse($result);
		$response->addCacheableDependency($result);
		
		return $response;
	
	}
	
}
