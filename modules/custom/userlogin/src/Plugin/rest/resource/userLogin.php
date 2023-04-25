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

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "userLogin_post_user_login",
 *   label = @Translation("Plus Post User Login"),
 *  serialization_class = "",
 *   uri_paths = {
 *      "canonical" = "/api/userLogin",
 *     "create" = "/api/userLogin",
 *   }
 * )
 */
class userLogin extends ResourceBase {

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
        $headers_jwt = getAllHeaders();
        $decoded = json_decode(base64_decode(str_replace('_', '/', str_replace('-', '+', explode('.', $headers_jwt['jwt'])[1]))));
       // echo "<pre>";print_r($decoded);exit;
		// $decoded->preferred_username = "ravi567@trianz.com";
       // $users = \Drupal::entityTypeManager()->getStorage('user')
              //  ->loadByProperties(['mail' => $decoded->preferred_username]);
			//echo "dfs";exit;	
				//$users = \Drupal::entityTypeManager()->getStorage('user')->load(1);
				$ids = \Drupal::entityQuery('user')->condition('mail', $decoded->preferred_username)->execute();
                $users = User::loadMultiple($ids);
				//echo "<pre>";print_r($users);exit;
        $user = reset($users);
      //  echo "<pre>";print_r($user);exit;
        if ($user) {
            $uid = $user->id();
            $user_info = user_login_finalize($user);
        } else {
			
            $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
            $user = \Drupal\user\Entity\User::create();

//Mandatory settings
            $user->setPassword('password');
            $user->enforceIsNew();
            $user->setEmail($decoded->preferred_username);
            $user->setUsername($decoded->preferred_username); //This username must be unique and accept only a-Z,0-9, - _ @ .
			
//Optional settings
            $user->set("init", $decoded->preferred_username);

            //$user->set("field_full_name", $decoded->name);
            $user->set("langcode", $language);
			 $user->set("name", $decoded->preferred_username);
            $user->set("preferred_langcode", $language);
            $user->set("preferred_admin_langcode", $language);
			
            //$user->set("setting_name", 'setting_value');
			
             $user->activate();
			
			
//Save user
         //   $res = $user->save();
           // $user->id = $user->uid;
			
			
			
               
                // Update the field.
             //   $user->set('field_receive_updates', $receive_updates);
               // $user->set('field_receive_news', $receive_news);
                //$user->set('field_receive_event_update', $receive_event_updates);
                //$user->set('field_receive_group_updates', $receive_group_updates);         
                // Save the Paragraph.
                $user->save();
//exit;
            $user_info = user_login_finalize($user);
			
        }
	
        $request = \Drupal::request();
        $session_manager = \Drupal::service('session_manager');
        $session_id = $session_manager->getId();
		
        $user_id = \Drupal::currentUser()->id();
        $user_entity = \Drupal::entityTypeManager()->getStorage('user')->load($user->id());
        $csrf_token = \Drupal::csrfToken()->get('rest');

        $user_object['current_user'] = array();
        $user_object['current_user']['uid'] = $user->id();
        $user_object['current_user']['roles'] = array();
        $user_object['current_user']['name'] = $decoded->preferred_username;
        $user_object['current_user']['csrf_token'] = $csrf_token;
        $user_object['current_user']['logout_token'] = $session_id;
        $response = new ResourceResponse($user_object);
        $response->addCacheableDependency($user_object);
        return $response;
    }

}
