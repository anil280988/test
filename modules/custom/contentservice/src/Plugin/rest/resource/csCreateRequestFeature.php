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
use Drupal\Component\Utility\Crypt;
use \Firebase\JWT\JWT;
use \Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\domain\DomainInterface;
use Drupal\contentservice\Base64Image;
use Drupal\contentservice\GenericService;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "concierto_cs_createRequestFeature",
 *   label = @Translation("concierto cs CreateRequestFeature"),
 *  serialization_class = "",
 *   uri_paths = {
 *      "canonical" = "/api/csCreateRequestFeature",
 *     "create" = "/api/csCreateRequestFeature",
 *   }
 * )
 */
class csCreateRequestFeature extends ResourceBase {

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
   *
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
      $message = "Client Access Deny, Kindly contact Site Admin";
      throw new AccessDeniedHttpException($message);
    }
		if(empty($data['title'])) {
			$message="Kindly fill all mandatory fields";
	        throw new AccessDeniedHttpException($message);
	    }
		if($data['title']!=strip_tags($data['title'])) {
		    $message="Html tags are not allowed in title.";
	        throw new AccessDeniedHttpException($message);
		}
		if($data['description'] != strip_tags($data['description'])) {
		    $message="Html tags are not allowed in title.";
	        throw new AccessDeniedHttpException($message);
		}
		$node = Node::create(
		  array(
		    'type' => 'request_a_feature',
		    'title' => $data['title'],
		    'status' => true
		  )
		);
		$node->set('field_domain_access', $domain_id);
		$node->set('field_feature_description_',$data['description']);
		if(!empty($data['file'])) {
			$extentionArray = ["png", "jpg","jpeg","pdf","txt","csv","doc","docs","xls","xlsx","xlsm","xlsb","xlts","dot","dotx","eml"];
			$fileUrl=$data['file'];
			$fileUrlArray = explode(".",$fileUrl);
			$extention = end($fileUrlArray);
			$imageNameArray = explode("/",$fileUrl);
			$imageName = end($imageNameArray);
			if(in_array($extention,$extentionArray)){
				if (strpos($imageName, ':')!==false || strpos($imageName, '*')!== false || strpos($imageName, '?')!==false || strpos($imageName, '|')!==false || strpos($imageName, '>')!==false || strpos($imageName, '<')!==false) {
					$message="Invalid File Format";
					throw new AccessDeniedHttpException($message);
				} else {
					$node->set('field_feature_file', $data['file']);
				}
			} else {
				$message="Invalid File Format";
				throw new AccessDeniedHttpException($message);
			}
	    }
		$node->save();
		
		$result['response'] = ['status' => 'success', 'message' => 'Requested Feature is submitted Successfully',
		'feature_id' => $node->id()
		];

		$response = new ResourceResponse($result);
		$response->addCacheableDependency($result);
		return $response;
    }	
}
