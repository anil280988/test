<?php

namespace Drupal\news\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Psr\Log\LoggerInterface;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use \Drupal\file\Entity\File;
use Drupal\contentservice\GenericService;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "update_user_broadcast_rest_resource_post",
 *   label = @Translation("update_user_broadcast_rest_resource_post"),
 *  serialization_class = "",
  *   uri_paths = {
 *      "canonical" = "/api/updateBroadcast/{id}",
 *   }
 * )
 */

class updateBroadcast extends ResourceBase {

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
      $container->get('logger.factory')->get('broadcast'),
      $container->get('current_user')
    );
  }

 /**
   * Responds to put requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @param $node_type
   * @param $data
   * @return \Drupal\rest\ResourceResponse Throws exception expected.
   * Throws exception expected.
   */
 public function put($id) {
	$data = json_decode(\Drupal::request()->getContent(), true);
    $node = Node::load($id);
	//check Domain - 
	$client_id = \Drupal::request()->headers->get('Client-Id');
	
	/** @var \Drupal\contentservice\Service\GenericService $service */
	$service = \Drupal::service('contentservice.GenericService');
    $domain_id = $service->getDomainIdFromClientId($client_id);
	
	$domain_access = $node->get('field_domain_access')->target_id;
	if($domain_access != $domain_id) {
	$message="Domain Access Deny";
	throw new AccessDeniedHttpException($message);
	}
    $node->set('title', $data['title']);
    $node->save();
	  $result['response'] = ['status' => 'success', 'message' => 'Broadcast is Updated Successfully',
	 'broadcastId' => $node->id()
	];
	$response = new ResourceResponse($result);
	$response->addCacheableDependency($result);
	return $response;
}
}