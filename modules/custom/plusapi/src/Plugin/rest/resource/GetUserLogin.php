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
 *   id = "plus_post_user_login",
 *   label = @Translation("Plus Post User Login"),
 *  serialization_class = "",
 *   uri_paths = {
 *      "canonical" = "/api/getUserLogin",
 *     "create" = "/api/getUserLogin",
 *   }
 * )
 */
class GetUserLogin extends ResourceBase {

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
		
		\Drupal::logger('Login data')->warning('<pre><code>' . print_r($data, TRUE) . '</code></pre>');
        global $base_url;
        $config = \Drupal::config('xai.settings');
		$login_api = $config->get('dev_env').'/user/login/new';
		\Drupal::logger('Login URL')->warning('<pre><code>' . print_r( $login_api, TRUE) . '</code></pre>');
        $usrName = $data['userName'];
        $password = $data['password'];
		$realm = $data['realm'];
        $user_agent = \Drupal::request()->headers->get('user-agent');
        $sysUserids = \Drupal::entityQuery('user')
                ->condition('status', 1)
                ->condition('field_username', $data['userName'])
                ->execute();
        $sysUsers = User::loadMultiple($sysUserids);
        $sysUserInfo = array();
        foreach ($sysUsers as $uid => $sysUser) {
            $sysUserInfo[$uid]['realmid'] = $sysUser->get('field_realmid')->value;
            $sysUserInfo[$uid]['mail'] = $sysUser->get('mail')->value;
        }

        if (empty($sysUserInfo)) {

            $user_object['data']['status'] = 201;
            $user_object['data']['message'] = "Please enter valid username password";
        } else {
            /* Http request to concirto login to get the user session */
            foreach ($sysUserInfo as $key => $postinfo) {
               // $realm = $postinfo['realmid'];
			   \Drupal::logger('old realm')->warning('<pre><code>' . print_r($postinfo['realmid'], TRUE) . '</code></pre>');
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $login_api);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS,
                        "user-agent=$user_agent&userName=$usrName&password=$password&mfaToken=&realm=$realm");
				\Drupal::logger('Login payload drupal')->warning('<pre><code>' . print_r("userName=$usrName&password=$password&mfaToken=&realm=$realm", TRUE) . '</code></pre>');
                // In real life you should use something like:
                // curl_setopt($ch, CURLOPT_POSTFIELDS, 
                //          http_build_query(array('postvar1' => 'value1')));
                // Receive server response ...
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $server_output = curl_exec($ch);
                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $conciertoRes = json_decode($server_output);
				\Drupal::logger('Login conciertoRes')->warning('<pre><code>' . print_r( $conciertoRes, TRUE) . '</code></pre>');
                if ($conciertoRes->status == 200) {
                    $conciertoData = json_decode($server_output);
                    $email = $postinfo['mail']; 
                    break;
                }
                curl_close($ch);
            }
            if ($conciertoRes->status != 200) {
                $user_object['data']['status'] = 201;
                $user_object['data']['message'] = $conciertoRes->message;
            } else {
                /* End of Http request to concirto login to get the user session */

                $ids = \Drupal::entityQuery('user')->condition('mail', $email)->execute();
                $users = User::loadMultiple($ids);
                $user = reset($users);
                if ($user) {
                    $uid = $user->id();
                    $user_info = user_login_finalize($user);
                } 
                $request = \Drupal::request();
                $session_manager = \Drupal::service('session_manager');
				//print_r($session_manager); 
                $session_id = $session_manager->getId();
				$session_name = $session_manager->getName();
				
                $user_id = \Drupal::currentUser()->id();
                $user_entity = \Drupal::entityTypeManager()->getStorage('user')->load($user->id());
                $csrf_token = \Drupal::csrfToken()->get('rest');
                $user_object['current_user'] = array();
				$user_object['status'] = 200;  
                $user_object['current_user']['drupalData']['uid'] = $user->id();
                $user_object['current_user']['drupalData']['roles'] = $user->getRoles();
                $user_object['current_user']['drupalData']['name'] = $user->get('mail')->value;
                $user_object['current_user']['drupalData']['csrf_token'] = $csrf_token;
                $user_object['current_user']['drupalData']['logout_token'] = $session_id;
				$user_object['current_user']['drupalData']['login_SESS'] = $session_name; 				
                $conciertoToken = $conciertoData->data->sessionData->access_token->token;
                $conciertoRefreshToken = $conciertoData->data->sessionData->refresh_token->token;
				$user_object['current_user']['conciertoData']['username'] = $conciertoData->data->username;
                #$user_object['current_user']['conciertoData']['userRole'] = $conciertoData->data->userRole;
                $user_object['current_user']['conciertoData']['email'] = $conciertoData->data->email;
                $user_object['current_user']['conciertoData']['userId'] = $conciertoData->data->userId;
				$user_object['current_user']['conciertoData']['userType'] = $conciertoData->data->userType;
               
                $user_object['current_user']['conciertoData']['access_token'] = $conciertoToken;
                $user_object['current_user']['conciertoData']['refresh_token'] = $conciertoRefreshToken;
            } 
        }
        $response = new ResourceResponse($user_object);
        $response->addCacheableDependency($user_object);
        return $response;
    }

}
