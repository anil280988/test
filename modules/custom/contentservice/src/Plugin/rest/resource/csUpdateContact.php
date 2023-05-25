<?php

namespace Drupal\contentservice\Plugin\rest\resource;

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
 *   id = "concierto_cs_update_contact",
 *   label = @Translation("concierto cs update contact"),
 *  serialization_class = "",
 *   uri_paths = {
 *      "canonical" = "/api/csUpdateContact/{id}",
 *   }
 * )
 */
class csUpdateContact extends ResourceBase {

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
    public function put($id) { 
		/** @var \Drupal\contentservice\Service\GenericService $service */
		$service = \Drupal::service('contentservice.GenericService');
		//Set Domain - 
		$client_id = \Drupal::request()->headers->get('Client-Id');
		$login = $service->userDuplicateLoginValidation();
		if($login!='1'){
			$message="Invalid Login";
			throw new AccessDeniedHttpException($message);
		}
		$domain_id = $service->getDomainIdFromClientId($client_id);
		if(empty($domain_id)) {
			$message="Client Access Deny, Kindly contact Site Admin";
	        throw new AccessDeniedHttpException($message);
		}
		
	//Permission Check for the users
    $has_content_permission = $service->UserPermissionCheck('Contact','update');
	if($has_content_permission=='Allow') {
	  $data = json_decode(\Drupal::request()->getContent(), true);
	  if(empty($data['name']) || empty($data['title']) || empty($data['email']) || !is_numeric($data['mobile']) || empty($data['region'])) {
	    $message="Kindly fill all mandatory fields";
	    throw new AccessDeniedHttpException($message);
	  }
	  if($data['name'] != strip_tags($data['name'])) {
	    $message="Html tags are not allowed in name.";
	    throw new AccessDeniedHttpException($message);
	  }
	  if($data['title'] != strip_tags($data['title'])) {
	    $message="Html tags are not allowed in title. ";
	    throw new AccessDeniedHttpException($message);
	  }
	  if($data['region'] != strip_tags($data['region'])) {
	    $message="Html tags are not allowed in region. ";
	    throw new AccessDeniedHttpException($message);
	  }
	  if(!empty($data['photo'])) {
		$extentionArray = ["jpg","jpeg","svg","png","gif","tiff","psd"];
		$imageUrl=$data['photo'];
		$imageUrlArray = explode(".",$imageUrl);
		$extention = end($imageUrlArray);
		if(!in_array($extention,$extentionArray)) {
			$message="Invalid Image Format";
			throw new AccessDeniedHttpException($message);
		}
	  }
      $node = Node::load($id);
	  if(!is_object($node)) {
	    $message="Invalid Id";
	    throw new AccessDeniedHttpException($message);
	    return new JsonResponse([$message]);
	  }
	  $domain_access = $node->get('field_domain_access')->getValue()[0]['target_id'];
   
	  $node->set('title', $data['name']);
	  $node->set('field_contact_email', $data['email']);
	  $node->set('field_contact_title', $data['title']);
	  $node->set('field_mobile', $data['mobile']);
	  $node->set('field_region', $data['region']);
	  if(!empty($data['name'])) {
	    $node->set('field_profile_image_url', $data['photo']);
	  }
      $node->save();
	  $result['response'] = ['status' => 'success', 'message' => 'Contact is Updated Successfully',
	 'contactId' => $node->id()
	  ];
	  return new JsonResponse($result);
    } else {
	$message="Access Deny";
	throw new AccessDeniedHttpException($message);
    }
  }

}
