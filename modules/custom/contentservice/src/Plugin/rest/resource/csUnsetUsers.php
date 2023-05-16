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
 *   id = "concierto_cs_unset_users",
 *   label = @Translation("concierto cs unset user"),
 *  serialization_class = "",
 *   uri_paths = {
 *      "canonical" = "/api/csUnsetUsers",
        "create" = "/api/csUnsetUsers",
 *   }
 * )
 */
class csUnsetUsers extends ResourceBase {

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
   * Responds to POST requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @param $data
   * @return \Drupal\rest\ResourceResponse Throws exception expected.
   * Throws exception expected.
   */	
  public function post($data) {
    $data = json_decode(\Drupal::request()->getContent(), true);
    $user = user_load_by_mail($data['mail']);
    if(isset($data['realm'])){
      if(!empty($user) && ($user->get('field_realmid')->value != $data['realm'])) {
		//Duplicate User-
		$duplicateEmail = $realm.'###'.$decodedData->email;
		$duplicateUser = user_load_by_mail($duplicateEmail);
		if(!empty($duplicateUser)) {
		  $user = $duplicateUser;
		} else {
		  $user = '';
		  $result = ['status' => 'failed', 'message' => 'Invalid User'];
		} 
	  }
	}
	if(is_object($user)){
	  $user->set('status',0);
	  $user->save();
	  $result = ['status' => 'success', 'message' => 'User is Delete Successfully', 'userID' => $user->id()];
	} else {
	    $result = ['status' => 'failed', 'message' => 'Invalid User'];
	}
	$response = new ResourceResponse($result);
	$response->addCacheableDependency($result);
	return $response;
  }

}

