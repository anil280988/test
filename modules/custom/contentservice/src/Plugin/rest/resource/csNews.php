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
 *   id = "concierto_cs_show_news",
 *   label = @Translation("concierto cs show news"),
 *  serialization_class = "",
 *   uri_paths = {
 *      "canonical" = "/api/csNews/{id}",
 *   }
 * )
 */
class csNews extends ResourceBase {

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
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get($id) {
	$client_id = \Drupal::request()->headers->get('Client-Id');
	/** @var \Drupal\contentservice\Service\GenericService $service */
	$service = \Drupal::service('contentservice.GenericService');
	$login = $service->userDuplicateLoginValidation();
	if($login!='1'){
	  $message="Invalid Login";
	  throw new AccessDeniedHttpException($message);
	}
	if(is_numeric($id)) {
	  $nodeEntity = Node::load($id);
	  if(!is_object($nodeEntity)) {
		$message="Invalid Id";
		throw new AccessDeniedHttpException($message);
	  }

	  $result = [
		"id"=> $nodeEntity->id(),	
		"uid"=> $nodeEntity->get('uid')->getValue()[0]['target_id'],
		"uuid"=> $nodeEntity->uuid(),
		"title"=> $nodeEntity->get('title')->getValue()[0]['value'],
		"category"=> $nodeEntity->get('field_news_categories')->getValue()[0]['value'],
		"description"=> $nodeEntity->get('field_news_description')->getValue()[0]['value'],
		"trending"=> $nodeEntity->get('field_trending_news')->getValue()[0]['value'],
		"banner"=> $nodeEntity->get('field_news_banner_url')->getValue()[0]['value'],
		"tags"=> $nodeEntity->get('field_news_tags')->getValue()[0]['value'],
		"created"=> date('Y-m-d H:i:s', $nodeEntity->get('created')->getValue()[0]['value']),
	  ];
	  $response = new ResourceResponse($result);
	  $response->addCacheableDependency($result);
	  return $response;		
	}
  }
	
}