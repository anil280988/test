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
 *   id = "plus_post_user_password",
 *   label = @Translation("Plus Post User Password"),
 *  serialization_class = "",
 *   uri_paths = {
 *      "canonical" = "/api/getUserPassword",
 *     "create" = "/api/getUserPassword",
 *   }
 * )
 */
class getUserPassword extends ResourceBase {

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

        $usrName = $data['userName'];
    
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
            $user_object['data']['message'] = "Please enter valid username";
        } else {
            /* Http request to concirto login to get the user session */
            foreach ($sysUserInfo as $key => $postinfo) {
                $realm = $postinfo['realmid'];
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://ibmdev.concierto.in/api/iam/idp-User/executeActionsEmail");
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS,
                        "userName=$usrName&realm=$realm");
               
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $server_output = curl_exec($ch);
                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $conciertoRes = json_decode($server_output);
				if ($conciertoRes->status == 'success') {
                    //$conciertoData = json_decode($server_output);
                    //$email = $postinfo['mail'];
					  $user_object['data']['status'] = 200;
                      $user_object['data']['message'] = $conciertoRes->message;
                    break;
                }
                curl_close($ch);
            }
            if ($conciertoRes->status != 'success') {
                $user_object['data']['status'] = 201;
                $user_object['data']['message'] = $conciertoRes->message;
            } 
        }
        $response = new ResourceResponse($user_object);
        $response->addCacheableDependency($user_object);
        return $response;
    }

}
