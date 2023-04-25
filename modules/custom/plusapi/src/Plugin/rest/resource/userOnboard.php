<?php

namespace Drupal\plusapi\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\jwt\Transcoder\JwtTranscoder;
use \Firebase\JWT\JWT;
use \Drupal\Core\Access\CsrfTokenGenerator;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource( 
 *   id = "userOnboard_post_user_login",
 *   label = @Translation("User Creation"),
 *  serialization_class = "",
 *   uri_paths = {
 *      "canonical" = "/api/userOnboard",
 *     "create" = "/api/userOnboard",
 *   }
 * )
 */
class userOnboard extends ResourceBase {

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
		
		
	
        global $base_url;
        $otp = rand ( 100000 , 999999 );		
        $headers_jwt = getAllHeaders();
        $decoded = json_decode(base64_decode(str_replace('_', '/', str_replace('-', '+', explode('.', $headers_jwt['jwt'])[1]))));
       	
			$ids = \Drupal::entityQuery('user')->condition('uid', $decoded->drupal->uid)->execute();
            
			$users = User::loadMultiple($ids); 
			 
			
        $log_user = reset($users);
        
		 $domain_roles = \Drupal::service('config.factory')->get('domain_role.roles');
		//echo "<pre>";	print_r($domain_roles);exit;
		
		if ($usr = reset($log_user)) {
			
			$valid_roles = reset($usr['roles']);

			foreach($valid_roles as $key=>$valid_role)
			{
				$chk_role[$key] = $valid_role['target_id'];
			}
			
			if(in_array("site_admin",$chk_role)){
			
		
			
			$language = \Drupal::languageManager()->getCurrentLanguage()->getId();
            $user = \Drupal\user\Entity\User::create();

//Mandatory settings
            $user->setPassword('password');
            $user->enforceIsNew();
            $user->setEmail($data['mailid']);
            $user->setUsername($data['mailid']); //This username must be unique and accept only a-Z,0-9, - _ @ .
			$user->set("field_email",$data['mailid']); 
			
//Optional settings
            $user->set("init", $data['mailid']);

            //$user->set("field_full_name", $decoded->name);
            $user->set("langcode", $language);
			 $user->set("name", $data['mailid']);
            $user->set("preferred_langcode", $language);
            $user->set("field_first_name", $data['fname']);
            $user->set("field_last_name", $data['lname']);
            $user->set("field_otp", $otp);
            $user->set("preferred_langcode", $language);
            $user->set("preferred_admin_langcode", $language);
			//$user->addRole('whirlpool_review');
			//$user->addRole($data['roles']);
			 //$roles=$data['roles'];
			  $roles = array_diff($data['roles'], array("authenticated"));
			  foreach($roles as $role) {
			  $user->addRole("$role");
			 }
			
			
			$user->set("field_domain_access",$usr['field_domain_access']['x-default'][0]);
			
			if($user->save()){
				$user_object['data']['status']  = 200;
				$user_object['data']['message']  = "User created succesfully";
				$email_send = \Drupal::service('plugin.manager.mail')->mail('otp', 'send_otp', $user->getEmail(), \Drupal\Core\Language\Language::LANGCODE_DEFAULT, ['user' => $user, 'otp' => $otp, 'uuid' => $user->uuid()]);
			}else{ 
			$user_object['data']['status']  = 402;
				$user_object['data']['message']  = "There was some issue with user creation please contact Adminstator";
				
			} 
          			
			}else{
				
				$user_object['data']['status']  = 402;
				$user_object['data']['message']  = "User authencation failed 1";	
			}
	
	} else {
		           $user_object['data']['status']  = 402;
				$user_object['data']['message']  = "User authencation failed 2";
                			
        }
	
      

        $response = new ResourceResponse($user_object);
        $response->addCacheableDependency($user_object);
        return $response;
    }

}
