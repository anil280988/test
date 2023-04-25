<?php

namespace Drupal\plusapi\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;
use Drupal\user\Entity\User;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "plus_get_user_info",
 *   label = @Translation("Plus Get User Info"),
 *   uri_paths = {
 *     "canonical" = "/api/getUserInfo/{uid}"
 *   }
 * )
 */
class GetUserInfo extends ResourceBase {
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
     * Responds to GET requests.
     *
     * Returns a list of bundles for specified entity.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws exception expected.
     */
    public function get($uid) {
        global $base_url;
		$userId = \Drupal::currentUser()->id();
		if ($uid != $userId && $userId !=0) {
			$result['status'] = '403';
			$result['message'] = 'access denied';
			$response = new ResourceResponse($result);
			$response->addCacheableDependency($result);
			return $response;
		}
        if (!empty($userId)) {
            $entities = \Drupal::entityQuery('user')
                    ->condition('status', 1)
                    ->condition('uid',  $userId)
                    ->accessCheck(false)
                    ->execute();
            if (!empty($entities)) {
                $users = \Drupal::entityTypeManager()->getStorage('user')->loadMultiple($entities);
                $index = 1;
                foreach ($users as $key => $user) {
                    $username = $user->get('name')->value;
					$result['id'] = $key;
                    $result['profile_id'] = $user->get('uid')->value;
					$result['domain'] = $user->get('field_domain_access')->target_id;
                    $result['fname'] = $user->get('field_first_name')->value;
                    $result['lname'] = $user->get('field_last_name')->value;
                    $result['email'] = $user->get('field_email')->value;
                    $result['role'] =  $user->getRoles();
                    $result['added'] = $user->get('created')->value;
                    $result['changed'] = $user->get('changed')->value;
                    $result['logged'] = $user->get('login')->value; 
					$result['apitokenconcierto'] = $user->get('field_apitokenconcierto')->value;
					$result['clientid'] = $user->get('field_clientid')->value;
					$result['conciertouserid'] = $user->get('field_conciertouserid')->value;
					$result['createdtimestamp'] = $user->get('field_createdtimestamp')->value;
					$result['emailverified'] = $user->get('field_emailverified')->value;
					$result['enabled'] = $user->get('field_enabled')->value;
					$result['keyclockuserid'] = $user->get('field_keyclockuserid')->value;
					$result['realmid'] = $user->get('field_realmid')->value;
					$result['sourceuserid'] = $user->get('field_sourceuserid')->value;
					$result['totp'] = $user->get('field_totp')->value;
					$result['username'] = $user->get('field_username')->value;
					$user_roles = $user->getRoles();
				    if (in_array("authenticated",$user_roles)) {
					  $user_role = 'authenticated';
					}
					if (in_array("content_editor",$user_roles)) {
					  $user_role = 'content_editor';
					}
					if (in_array("content_reviewer",$user_roles)) {
					  $user_role = 'content_reviewer';
					}
					if (in_array("site_admin",$user_roles)) {
					  $user_role = 'site_admin';
					}	
					$entitiesRoles = \Drupal::entityQuery('node')
						->condition('status', 1)
						->condition('type', 'custom_permissions')
						->condition('title', $user_role, 'CONTAINS')
						->condition('langcode','en')
						->accessCheck(false)
						->execute();
					if (!empty($entitiesRoles)) {
					  $nodesRoles = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entitiesRoles);			     
					  $result['_access'] = array();
					  foreach ($nodesRoles as $key => $entityRoles) {
					    // $result['_access'][strtolower($entityRoles->field_module_name->value)] =>  str_replace(array("\n", "\r", "\""), '', $entityRoles->field_permission_set->value),
					    $result['_access'][trim(strtolower($entityRoles->field_module_name->value))]['create'] =  (int)$entityRoles->field_create->value;
					    $result['_access'][trim(strtolower($entityRoles->field_module_name->value))]['read'] =  (int)$entityRoles->field_view->value;
					    $result['_access'][trim(strtolower($entityRoles->field_module_name->value))]['update'] =  (int)$entityRoles->field_update->value;
					    $result['_access'][trim(strtolower($entityRoles->field_module_name->value))]['delete'] =  (int)$entityRoles->field_delete->value;
					  }
					}
                }
                $response = new ResourceResponse($result);
            } else {
                $result = "User: " . $userId . " doesn't exist";
                $response = new ResourceResponse($result, 400);
            }
        }
        return $response;
    }
}
