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
 *   id = "roles_get_user_info",
 *   label = @Translation("Roles Info"),
 *   uri_paths = {
 *     "canonical" = "/api/rolesInfo"
 *   }
 * )
 */
class rolesInfo extends ResourceBase {

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
    public function get() {

        global $base_url;
        // You must to implement the logic of your REST Resource here.
        // Use current user after pass authentication to validate access.
        /* if (!$this->currentUser->hasPermission('access content')) {
            throw new AccessDeniedHttpException();
        }
        */
        $rls = user_role_names();
		
		
		foreach( $rls as $key => $value ) {
        if( strpos( $key, 'anonymous' ) === 0 ) {
        unset( $rls[ $key ] );
        }
		if( strpos( $key, 'ibm_admin' ) === 0 ) {
        unset( $rls[ $key ] );
        }
		if( strpos( $key, 'administrator' ) === 0 ) {
        unset( $rls[ $key ] );
        }
        }
		
        $response = new ResourceResponse($rls);
        return $response;
   

}
}