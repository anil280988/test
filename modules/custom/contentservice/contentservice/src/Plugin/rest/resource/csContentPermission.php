<?php

namespace Drupal\mspclientdomain\Plugin\rest\resource;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Session\AccountProxyInterface;
use \Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\contentservice\GenericService;
use Drupal\jwt\Transcoder\JwtTranscoder;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\Component\Utility\Crypt;
use Drupal\domain\DomainInterface;
use Drupal\rest\ResourceResponse;
use Drupal\core\modules\taxonomy;
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
 *   id = "concierto_cs_permission",
 *   label = @Translation("concierto cs content permission data"),
 *  serialization_class = "",
 *   uri_paths = {
 *      "canonical" = "/api/csContentPermission",
 *      "create" = "/api/csContentPermission",
 *   }
 * )
 */
class csContentPermission extends ResourceBase {

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
	/** @var \Drupal\contentservice\Service\GenericService $service */
	$service = \Drupal::service('contentservice.GenericService');
	if($login!='1'){
	  $message="Invalid Login";
	  //throw new AccessDeniedHttpException($message);
	}
	$client_id = \Drupal::request()->headers->get('Client-Id');
	$domain_id = $service->getDomainIdFromClientId($client_id); 
	$query = \Drupal::entityQuery('node');
	$query->condition('type', 'menu_list');
	$query->condition('langcode', 'en');
	$query->condition('status', 1);
	$query->condition('field_domain_access', $domain_id);
	$query->sort('created', 'DESC');
	$query->accessCheck(false);
	$query->range(0, 1);
	$entities = $query->execute();
 
	if(!empty($entities)){
	  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entities);
	  foreach ($nodes as $key => $entity) {
	    $feature = $entity->get('field_feature_permission')->getValue();
		$result = [];
		foreach ($feature as $key => $value) {
		  array_push($result,$value['value']);
		}
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