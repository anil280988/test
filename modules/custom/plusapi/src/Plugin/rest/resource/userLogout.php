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
 *   id = "cs_user_logout",
 *   label = @Translation("User Logout"),
 *  serialization_class = "",
 *   uri_paths = {
 *      "canonical" = "/api/userLogout",
 *     "create" = "/api/userLogout",
 *   }
 * )
 */
class userLogout extends ResourceBase {

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
     * Logout the current user.
     *
     * @param $data
     * @return \Drupal\rest\ResourceResponse Throws exception expected.
     * Throws exception expected.
     */
    public function post($data) {
		$uid = $data['uid'];
		$userId = \Drupal::currentUser()->id();
		 \Drupal::logger('Loggout api - $uid')->warning($uid);
		 \Drupal::logger('Loggout api - $userId')->warning($userId);
		if($uid !=  $userId) {
			$result['status'] = '403';
			$result['message'] = 'access denied';
			$response = new ResourceResponse($result);
			$response->addCacheableDependency($result);
			return $response;
		 }
		$account = User::load($uid);
		\Drupal::currentUser()->setAccount($account);
		if (\Drupal::currentUser()->isAuthenticated()) {
		  $session_manager = \Drupal::service('session_manager');
		  $session = \Drupal::request()->getSession();
		 $mainId = \Drupal::currentUser()->id();
		  \Drupal::logger('Loggout api - $actual logout id')->warning($mainId);
		  //$session_manager->delete(\Drupal::currentUser()->id());
		 // $session_manager->destroy();
		  user_logout();
		  $result['message'] = 'Successfully Logout';
		   $response = new ResourceResponse($result);
			$response->addCacheableDependency($result);
			return $response;
		}
		
		
		 }

}