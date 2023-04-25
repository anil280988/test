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

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "concierto_cs_createbroadcast",
 *   label = @Translation("concierto cs createbroadcast"),
 *  serialization_class = "",
 *   uri_paths = {
 *      "canonical" = "/api/csCreateBroadcast",
 *     "create" = "/api/csCreateBroadcast",
 *   }
 * )
 */
class csCreateBroadcast extends ResourceBase {

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
      $configuration, $plugin_id, $plugin_definition, $container->getParameter('serializer.formats'), $container->get('logger.factory')
      ->get('plusapi'), $container->get('current_user')
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
    public function post($data) {
	    /** @var \Drupal\contentservice\Service\GenericService $service */
		$service = \Drupal::service('contentservice.GenericService');
		//Set Domain - 
		$client_id = \Drupal::request()->headers->get('Client-Id');
		$domain_id = $service->getDomainIdFromClientId($client_id);
		if(empty($domain_id)) {
			$message="Client Access Deny, Kindly contact Site Admin";
	        throw new AccessDeniedHttpException($message);
		}
		//Permission Check for the users
		$has_content_permission = $service->UserPermissionCheck('Broadcast','create');
		if($has_content_permission == 'Allow') {
			if(empty($data['title'])) {
				$message="Kindly fill all mandatory fields";
				throw new AccessDeniedHttpException($message);
	        }
			//Create node with state and domain
			$node = Node::create(
			array(
			  'type' => 'broadcast',
			  'title' => $data['title'],
			  'status' => true
			)
			);
			
			if(isset($data['group'])){
			  $node->set('field_user_group', $data['group']);
			}	
			$node->set('field_domain_access', $domain_id);
			$node->save();
			$result['response'] = ['status' => 'success', 'message' => 'Broadcast is Created Successfully',
			'broadcastId' => $node->id()
			];
			$response = new ResourceResponse($result);
			$response->addCacheableDependency($result);

			return $response;
		}
		else {
			$message="Access Deny";
			throw new AccessDeniedHttpException($message);
		}
	}
	
}
