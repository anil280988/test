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
use Drupal\Tests\getjwtonlogin\Unit\EventSubscriber;
use Drupal\Component\Serialization\Json;
use Drupal\getjwtonlogin\EventSubscriber\JwtLoginSubscriber;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "cs_user_details",
 *   label = @Translation("User Details"),
 *  serialization_class = "",
 *   uri_paths = {
 *      "canonical" = "/api/getUserDetails",
 *     "create" = "/api/getUserDetails",
 *   }
 * )
 */
class GetUserDetails extends ResourceBase {

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
		/*Getting jwt token and decoding*/
		$userToken = $data['token'];
		$decodedData = json_decode(base64_decode(str_replace('_', '/', str_replace('-', '+', explode('.', $userToken)[1]))));
		if(empty($decodedData)) {
			$message="Invalid Token";
	        throw new AccessDeniedHttpException($message);
		}  
        if(!isset($decodedData->email)) { 
		    $message="Invalid Email Id";
	        throw new AccessDeniedHttpException($message);
		}
		
		/*Check exixting user*/ 
		$sysUser = user_load_by_mail($decodedData->email);  
		$realm = end(explode('/', $decodedData->iss));
		
		//Duplicate email -
		if(!empty($sysUser) && ($sysUser->get('field_realmid')->value != $realm)) {
			$duplicateEmail = $realm.'---'.$decodedData->email;
			$sysDuplicateUser = user_load_by_mail($duplicateEmail);
			
			if(empty($sysDuplicateUser)) {
				$sysUser = '';
				$decodedData->email = $realm.'---'.$decodedData->email; 
			} else{
				$sysUser = $sysDuplicateUser;
			}
		}
		/*Create new user*/
		$realmAddress= $decodedData->iss;
		$recordMatch = [];
		$userId = $decodedData->sub;
		$parameters = "userId=$userId&realm=$realm";
		$config = \Drupal::config('xai.settings');
		$conciertoUrl = $config->get('dev_env').'/user/createandmapUserToClientAndGroup';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $conciertoUrl);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POST,TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$parameters);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    $server_output = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$conciertoRes = json_decode($server_output);
		\Drupal::logger('conciertoRes')->warning('<pre><code>' . print_r($conciertoRes, TRUE) . '</code></pre>');
		if(empty($conciertoRes) || (!empty($conciertoRes->error))) {
			$user_object['data']['status'] = 201;
			$user_object['data']['concierto message'] = $conciertoRes->error;
			$user_object['data']['realm'] = $realm;
			$user_object['data']['userId'] = $userId;
		} else {
			/*Getting User details from concierto $conciertoObject*/
			$conciertoObject = ((array)$conciertoRes->user)[0];
			\Drupal::logger('conciertoRes conciertoObject')->warning('<pre><code>' . print_r($conciertoObject, TRUE) . '</code></pre>');
			/*Getting User attributes from concierto $conciertoObjectAttributes*/
			$conciertoObjectAttributes = ((array)$conciertoRes->user)['attributes'];
			\Drupal::logger('conciertoRes conciertoObjectAttributes')->warning('<pre><code>' . print_r($conciertoObjectAttributes, TRUE) . '</code></pre>');
			
			//Checking both formats of concierto response -
			 $groupId = (is_array($conciertoObjectAttributes->groupId)) ? $conciertoObjectAttributes->groupId[0] : $conciertoObjectAttributes->groupId;
			 if(empty($groupId)){
			   $groupId = (is_array($conciertoObject->groupId)) ? $conciertoObject->groupId[0] : $conciertoObject->groupId;
			 }
						 
			  $clientId = (is_array($conciertoObjectAttributes->clientId)) ? $conciertoObjectAttributes->clientId[0] : $conciertoObjectAttributes->clientId;
			 if(empty($clientId)){
			   $clientId = (is_array($conciertoObject->clientId)) ? $conciertoObject->clientId[0] : $conciertoObject->clientId;
			 }
			 
			  $userId = (is_array($conciertoObjectAttributes->userId)) ? $conciertoObjectAttributes->userId[0] : $conciertoObjectAttributes->userId;
			 if(empty($userId)){
			   $userId = (is_array($conciertoObject->userId)) ? $conciertoObject->userId[0] : $conciertoObject->userId;
			 }
		}	
		\Drupal::logger('conciertoRes source')->warning('<pre><code>' . print_r($conciertoObjectAttributes->source, TRUE) . '</code></pre>');
		\Drupal::logger('conciertoRes isIdpUser')->warning('<pre><code>' . print_r($conciertoObjectAttributes->isIdpUser, TRUE) . '</code></pre>');
		//Login Source check -
		$user_object['data']['isUserBlocked'] = 0;
		 if (!in_array("userportal", $conciertoObjectAttributes->source, TRUE)) {
			if($conciertoObjectAttributes->isIdpUser!= 'true') {
			    $user_object['data']['status'] = 201;
				$user_object['data']['login'] = 'You dont have permsission to login into this portal. Please contact administrator.';
				$user_object['data']['realm'] = $realm;
				$user_object['data']['userId'] = $userId; 
				$user_object['data']['isUserBlocked'] = 1;
				$response = new ResourceResponse($user_object);
				$response->addCacheableDependency($user_object);
				return $response;
			}	
		}
		if(isset($conciertoObject->officialEmail)) {
            $userAvailableCheck = user_load_by_name($realm.'---'.$conciertoObject->userName);
			//$isblocked = $userAvailableCheck->isBlocked();
			if(!empty($userAvailableCheck) && !($userAvailableCheck->isBlocked())) {
				$user = $userAvailableCheck;
			} else {
				if(!empty($userAvailableCheck)) {
					$userAvailableCheck->delete();
				}
				$language = \Drupal::languageManager()->getCurrentLanguage()->getId();
				$user = \Drupal\user\Entity\User::create();
				$user->setPassword('password');
				$user->enforceIsNew();
				$user->set("langcode", $language);
				$user->set("name", $decodedData->email); //$decodedData->email // $conciertoObject->officialEmail
				$user->set("preferred_langcode", $language);
				$user->set("preferred_admin_langcode", $language);
                $user->set('status', 1);
				\Drupal::logger('Duplicate User - decoded email')->warning('<pre><code>' . print_r($decodedData->email, TRUE) . '</code></pre>');	
				$user->setEmail($decodedData->email); //$conciertoObject->officialEmail
				$user->setUsername($realm.'---'.$conciertoObject->userName);
				$user->set('field_conciertouserid', $userId);
				$user->set('field_clientid', $clientId); 
				$user->set('field_first_name', $conciertoObject->firstName);
				$user->set('field_last_name', $conciertoObject->lastName);
				$user->set('field_keyclockuserid', $conciertoObject->keyCloakId);
				$user->set('field_realmid', $conciertoObject->keycloakRealm);
				$user->set('field_group_id', $groupId);
				//$user->set('field_vip', $conciertoObjectAttributes->vip);
				if($decodedData->email == $realm.'---'. $conciertoObject->officialEmail) {
					$user->set('field_duplicate_email', 1);
					\Drupal::logger('Duplicate User checkbox 1')->warning('<pre><code>' . print_r($decodedData->email, TRUE) . '</code></pre>');
				} else {
					$user->set('field_duplicate_email', 0);
					\Drupal::logger('Duplicate User checkbox 0')->warning('<pre><code>' . print_r($decodedData->email, TRUE) . '</code></pre>');
				}
				$user->set('field_email', $conciertoObject->officialEmail); //email boolen
				$user->activate();
				$user->save(); 
			}
			\Drupal::service('session_manager')->destroy ();
			\Drupal::service('session_manager')->delete($user->id());
			user_logout();
			
			$user_info = user_login_finalize($user);
			
			
		
			$user_entity = \Drupal::entityTypeManager()->getStorage('user')->load($user->id());
            $session_manager = \Drupal::service('session_manager');
			$csrf_token = \Drupal::csrfToken()->get('rest');
            $session_name = $session_manager->getName();
			$session_id = $session_manager->getId();
			
		
			$sessiondetail = $session_name . '=======> ' . $session_id;
			\Drupal::logger('userid for session')->warning('<pre><code>' . print_r($user->id(), TRUE) . '</code></pre>');
			\Drupal::logger('session detail')->warning('<pre><code>' . print_r($sessiondetail, TRUE) . '</code></pre>');
			$allses = \Drupal::database()->select('sessions', 's')
			  ->fields('s', ['uid', 'sid', 'hostname', 'timestamp'])
			  ->condition('s.uid', $user->id())
			  ->orderBy('s.timestamp', 'DESC')
			  ->execute()->fetchAll();
			\Drupal::logger('all sessions of user')->warning('<pre><code>' . print_r($allses, TRUE) . '</code></pre>');
			
		
		
			$user_object['current_user']['drupalData']['groupId'] = $groupId;
			$user_object['current_user']['drupalData']['uid'] = $user->id();
			$user_object['current_user']['drupalData']['roles'] = user_role_names();
			$user_object['current_user']['drupalData']['name'] = $user->get('field_first_name')->value;
			$user_object['current_user']['drupalData']['email'] = $user->get('field_email')->value;
			$user_object['current_user']['drupalData']['csrf_token'] = $csrf_token;
			$user_object['current_user']['drupalData']['logout_token'] = $session_id;
			$user_object['current_user']['drupalData']['login_SESS'] = $session_name; 				
			$user_object['current_user']['conciertoData']['username'] = $conciertoObject->userName;
			//$user_object['current_user']['conciertoData']['userRole'] = $conciertoData->data->userRole;
			$user_object['current_user']['conciertoData']['email'] = $conciertoObject->officialEmail;
			$user_object['current_user']['conciertoData']['userId'] = $conciertoObject->userId;
			$user_object['current_user']['conciertoData']['userType'] = $conciertoObject->userType;//$conciertoObject->userType;
			$user_object['current_user']['conciertoData']['clientid'] = $user->get('field_clientid')->value;
            $user_object['current_user']['drupalData']['field_duplicate_email'] = $user->get('field_duplicate_email')->value;
			$user_object['current_user']['drupalData']['field_email'] = $user->get('field_email')->value;
			$user_object['current_user']['conciertoData']['vipUser'] = $conciertoObjectAttributes->vip;
		}					
		$response = new ResourceResponse($user_object);
		$response->addCacheableDependency($user_object);
		return $response;	
    }
}