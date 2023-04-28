<?php

namespace Drupal\mspclientdomain\Plugin\rest\resource;

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
 *   id = "concierto_cs_domain_mapping",
 *   label = @Translation("concierto cs domain mapping"),
 *  serialization_class = "",
 *   uri_paths = {
 *      "canonical" = "/api/csDomainMapping",
 *     "create" = "/api/csDomainMapping",
 *   }
 * )
 */
class csDomainMapping extends ResourceBase {

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
    public function post($data) {
	\Drupal::logger('Domian Data')->warning('<pre><code>' . print_r($data, TRUE) . '</code></pre>');
	$client_id = \Drupal::request()->headers->get('Client-Id');
	
	$vid = 'domain_client_map';
	$terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
	foreach ($terms as $term) {
		if($client_id ==$term->name) {
			$result[] = 'client already available';
			$response = new ResourceResponse($result);
			$response->addCacheableDependency($result);
			return $response;
		}
		 $term_data[] = array(
		  'id' => $term->tid,
		  'name' => $term->name
		 );
	}
		
	if(empty($data['clientName'])) {	
	$hostname = $data['clientName']; //'user.test4'
	} else {
		$result[] = 'Invalid clientName.';
			$response = new ResourceResponse($result);
			$response->addCacheableDependency($result);
			return $response;
	}
	if(empty($data['parentName'])) {	
	$name = $data['parentName']; //'user.test4' 
	} else {
		$result[] = 'Invalid Domain parentName';
			$response = new ResourceResponse($result);
			$response->addCacheableDependency($result);
			return $response;
	}
	
	
	
	/** @var \Drupal\contentservice\Service\GenericService $service */
	$service = \Drupal::service('contentservice.GenericService');
    $domain_id = $service->getDomainIdFromClientId($client_id);
	
	 if (empty($values)) {
     // $values['hostname'] = $this->createHostname();
     // $values['name'] = \Drupal::config('system.site')->get('name');
    }
	
	$id = \Drupal::entityTypeManager()->getStorage('domain')->createMachineName($hostname);
    $values = [
      'name' => $name,
      'hostname' => $hostname,
      'scheme' => 'http',
      'status' => 1,
      'weight' => 10,
      'is_default' => 0,
	 'id' => $id,
    ];
	$domain = \Drupal::entityTypeManager()->getStorage('domain')->create($values);
	$domain->save();
	echo $id;
	

	$new_term = Term::create([
		'vid' => "domain_client_map",
		'name' => $client_id,
		'domain' => $id,
	]);
	$new_term->field_domain->value = $id; //list 
	$new_term->field_cl->value = $data['clientStatus'];
	$new_term->save();
	
    $result['response'] = ['status' => 'success', 'message' => 'New Domain is Created Successfully'
    ];

	$response = new ResourceResponse($result);
	$response->addCacheableDependency($result);
	return $response;
	}

	
  
    public function getModerationState(){
	    $moderation_state = 'draft';
		$current_user = \Drupal::currentUser();
		$roles = $current_user->getRoles();
		if (in_array('content_editor', $roles)) {
		  $moderation_state = 'draft';
		}
		if (in_array('content_reviewer', $roles)) {
		  $moderation_state = 'review';
		}
        if (in_array('site_admin', $roles)) {
		  $moderation_state = 'published';
		}
		if (in_array('administrator', $roles)) {
		  $moderation_state = 'published';
		 		}
		return $moderation_state;
	}
	

	
	public function UserPermissionCheck($chkdata){
	   $user =\Drupal::currentUser()->getAccount();
	   $user_roles =$user->getRoles();
	   $roles_permissions = user_role_permissions($user_roles);	
	   $found_key = array_search($chkdata, $roles_permissions['authenticated']);
	   return $found_key;
		
	} 
	
}
