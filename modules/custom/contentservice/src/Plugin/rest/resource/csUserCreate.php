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
use Drupal\user\UserInterface;
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
 *   id = "concierto_csusercreate",
 *   label = @Translation("concierto csUserCreate"),
 *  serialization_class = "",
 *   uri_paths = {
 *      "canonical" = "/api/csUserCreate",
 *     "create" = "/api/csUserCreate",
 *   }
 * )
 */
class csUserCreate extends ResourceBase {

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

	  $groups = $data['representation']['groups'];
	  foreach($groups as $key=>$lowgroup){
		$lowgroups[$key] =  str_replace('/','',strtolower($lowgroup));
		}

	  if(isset($data['representation']['groups']) && in_array('userportal', $lowgroups)){

		if(isset($data['representation']['email'])){
			
          $user = user_load_by_mail($data['representation']['email']);
		  if($data['representation']['emailVerified'] == true) { 
            $status=1;
		  }else {
			$status=0; 
	      }
		  
		 if(empty($user)){

			 $values = [
		          'name'=> $data['representation']['email'],
				  'mail'=> $data['representation']['email'],
				  //'field_clientid'=> $data['clientid'],
				  //'field_conciertouserid'=>$data['conciertouserid'],
				  'field_createdtimestamp'=> date('Y-m-d\TH:i:s', substr($data['time'], 0, -3) ),
				  'field_emailverified'=> $data['representation']['emailVerified'],
				  'field_enabled'=> $data['representation']['enabled'],
				  'field_first_name'=> $data['representation']['firstName'],
                 'field_keyclockuserid'=>  str_replace('users/','',$data['resourcePath']),
                  'field_last_name'=> $data['representation']['lastName'],
				  'field_realmid'=> $data['realmId'],
				  //'field_sourceuserid'=> $data['sourceuserid'],
				  'field_totp'=> $data['representation']['totp'], 
				  'field_username'=>  $data['realmId'].'---'.$data['representation']['username'],
				  'field_clientid'=> $data['representation']['attributes']['clientId'][0],
				  'status'=> $status,
				];

				//no pass no otp mail field_emailverified=true/ else block
				$user = user_load_by_mail($data['representation']['email']);	
				if(empty($user)){
				$user = User::create($values); 
				$user->set("field_email",$data['representation']['email']);
                $user->setUsername($data['realmId'].'---'.$data['representation']['username']);				
                //Set Domain - 
				$client_id = \Drupal::request()->headers->get('Client-Id');

				/** @var \Drupal\contentservice\Service\GenericService $service */
				$service = \Drupal::service('contentservice.GenericService');
				$domain_id = $service->getDomainIdFromClientId($client_id);
				//Set Domain - 
				$user->set('field_domain_access', $domain_id); 
				$user->set('field_domain_admin', $domain_id);
 				
				//$user->addRole('authenticated');
				$user->enforceIsNew();
				$user->save(); 
				if($user->uid){

				$msg['data']['status'] = 'Success'; 
				$msg['data']['message'] = 'User Sucessfully Created'; 
				$msg['data']['userId'] = (int) $user->uid->value;
				} 
	  }			
		}else{ 
	              $user->set("field_email",$data['representation']['email']);
				  $user->set('field_createdtimestamp', date('Y-m-d\TH:i:s', substr($data['representation']['createdTimestamp'], 0, -3) ));
				  $user->set('field_emailverified', $data['representation']['emailverified']);
				  $user->set('field_enabled', $data['representation']['enabled']);
				  $user->set('field_first_name', $data['representation']['firstName']);
                 // $user->set('field_keyclockuserid', $data['representation']['id']);
                  $user->set('field_last_name', $data['representation']['lastName']);
				  $user->set('field_realmid', $data['realmId']);
				  $user->set('field_totp', $data['representation']['totp']);
				  $user->set('field_username',  $data['realmId'].'---'.$data['representation']['username']);
				  $user->set('field_clientid', $data['representation']['attributes']['clientId'][0]);
				  $user->set('status', $status);
				  $user->setUsername($data['realmId'].'---'.$data['representation']['username']);	
		          $user->save(); 
	            \Drupal::logger('user create inside  userid-$user->uid')->warning($user->uid);
				if($user->uid){
					\Drupal::logger('user create inside user created and uid')->warning($user->uid);
				$msg['data']['status'] = 'Success'; 
				$msg['data']['message'] = 'User Sucessfully Updated'; 
				$msg['data']['userId'] = (int) $user->uid->value;
				}
		    }
		}
	    $response = new ResourceResponse($msg); 
        $response->addCacheableDependency($msg);
        return $response;
}
	}
}
