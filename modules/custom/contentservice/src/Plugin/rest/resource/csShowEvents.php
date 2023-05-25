<?php

namespace Drupal\contentservice\Plugin\rest\resource;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Session\AccountProxyInterface;
use \Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\contentservice\GenericService;
use Drupal\jwt\Transcoder\JwtTranscoder;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\domain\DomainInterface;
use Drupal\rest\ResourceResponse;
use Drupal\taxonomy\Entity\Term;
use \Drupal\file\Entity\File;
use Psr\Log\LoggerInterface;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\user\Entity\Role;
use \Firebase\JWT\JWT;

/**
 * Provides a resource to get trending events listing.
 *
 * @RestResource(
 *   id = "concierto_cs_show_all_events",
 *   label = @Translation("concierto cs show events"),
 *  serialization_class = "",
 *   uri_paths = {
 *      "canonical" = "/api/csShowEvents",
 *   }
 * )
 */
class csShowEvents extends ResourceBase
{

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
    array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, AccountProxyInterface $current_user)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
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
  public function get()
  {
    //Get Domain from header -
    $client_id = \Drupal::request()->headers->get('Client-Id');
    /** @var \Drupal\contentservice\Service\GenericService $service */
    $service = \Drupal::service('contentservice.GenericService');
    $login = $service->userDuplicateLoginValidation();
    if ($login != '1') {
      $message = "Invalid Login";
      throw new AccessDeniedHttpException($message);
    }
    $domain_id = $service->getDomainIdFromClientId($client_id);
    $query = \Drupal::entityQuery('node');
    $query->condition('status', 1);
    $query->condition('type', 'trending_events');
    $query->condition('langcode', 'en');
    $query->condition('field_domain_access', $domain_id);
    $query->accessCheck(false);
    $entities = $query->execute();

    if (!empty($entities)) {
      $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entities);

      foreach ($nodes as $key => $entity) {
        $result[] = [
          "id" => $entity->id(),
          "event_desc" => $entity->get('field_event_details')->getValue()[0]['value'],
        ];
      }
    }
    if (empty($result)) {
      $result = [];
    }
    $response = new ResourceResponse($result);
    $response->addCacheableDependency($result);
    return $response;
  }
}
