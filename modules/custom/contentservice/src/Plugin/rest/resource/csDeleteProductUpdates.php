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

use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\contentservice\GenericService;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "concierto_cs_delete_productupdates",
 *   label = @Translation("concierto cs delete product updates"),
 *  serialization_class = "",
 *   uri_paths = {
 *      "canonical" = "/api/csDeleteProductUpdates/{id}",
 *   }
 * )
 */
class csDeleteProductUpdates extends ResourceBase {

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
    public function delete($id) {
	/** @var \Drupal\contentservice\Service\GenericService $service */
		$service = \Drupal::service('contentservice.GenericService');
		$login = $service->userDuplicateLoginValidation();
		if($login!='1'){
			$message="Invalid Login";
			throw new AccessDeniedHttpException($message);
		}
		//Set Domain - 
		$client_id = \Drupal::request()->headers->get('Client-Id');
		$domain_id = $service->getDomainIdFromClientId($client_id);
		if(empty($domain_id)) {
			$message="Client Access Deny, Kindly contact Site Admin";
	        throw new AccessDeniedHttpException($message);
		}
		//Permission Check for the users
		$has_content_permission = $service->UserPermissionCheck('Product Updates','create');	
		if($has_content_permission=='Allow') {
		  $node = Node::load($id);
		  if (!empty($node)) {
			$domain_access = $node->get('field_domain_access')->target_id;
			if($domain_access != $domain_id) {
			$message="Domain Access Deny";
			throw new AccessDeniedHttpException($message);
			}
			$product_type = $node->get('field_product_type')->value;
			if($product_type==1){ $msg="Changelog";}
			elseif($product_type==2){$msg="Procedures"; }
			elseif($product_type==3) {$msg="Roadmap";}
			$node->delete();   
			$result['response'] = [ 'message' => $msg .' is deleted Successfully'];
		  } else {
			  $result['response'] = ['status' => 'failure', 'message' => 'Invalid ProductUpdates '.$id];
			}
		} else {
		  $result['response'] = ['status' => 'failure', 'message' => 'Access Deny'];
		}
		return new JsonResponse($result);
	}
}

