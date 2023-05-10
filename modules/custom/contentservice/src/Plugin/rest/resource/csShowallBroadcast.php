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
    array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, AccountProxyInterface $current_user)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
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
  public function get()
  {
    //Get Domain from header -
    $client_id = \Drupal::request()->headers->get('Client-Id');
    /** @var \Drupal\contentservice\Service\GenericService $service */
    $service = \Drupal::service('contentservice.GenericService');
    $login = $service->userDuplicateLoginValidation();
    if ($login != '1') {
      $message = "Invalid Login";
      //throw new AccessDeniedHttpException($message);
    }
    $domain_id = $service->getDomainIdFromClientId($client_id);
    $query = \Drupal::entityQuery('node');
    $query->condition('status', 1);
    $query->condition('type', 'broadcast');
    $query->condition('langcode', 'en');
    $query->condition('field_domain_access', $domain_id);
    $query->accessCheck(false);
    $entities = $query->execute();

    if (!empty($entities)) {
      $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entities);
			
		foreach ($nodes as $key => $entity) {
		  $user = \Drupal\user\Entity\User::load($entity->uid->target_id);
			$id= $entity->uid->target_id;
			$database = \Drupal::database();
			$nidread= $entity->id();
			/*$query = $database->query("SELECT id FROM broadcast_users where nid=$nidread AND uid=$id");
			$results = $query->fetchAll();*/
			
			$query = \Drupal::database()->select('broadcast_users', 'bu');
			$query->addField('bu', 'id');
			$query->condition('bu.nid', $nidread);
			$query->condition('bu.uid', $id);
			$results = $query->execute()->fetchAll();
			$readcount = count($results);
			if($readcount>=1){$read=1;}else {$read=0;}
			$result['data'][] = [
				"id"=>  $entity->id(),	
				"uuid"=> $entity->uuid(),
				"title"=>  $entity->get('title')->getValue()[0]['value'],
				"group"=>  $entity->get('field_user_group')->getValue()[0]['value'],
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
