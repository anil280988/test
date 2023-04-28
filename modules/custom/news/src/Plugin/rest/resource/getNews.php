<?php

namespace Drupal\news\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Drupal\contentservice\GenericService;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "get_news_rest_resource",
 *   label = @Translation("get_news_rest_resource"),
 *   uri_paths = {
 *     "canonical" = "/api/getNews"
 *   }
 * )
 */

class getNews extends ResourceBase {

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
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('article'),
      $container->get('current_user')
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
    //Get Domain from headers - 
	$client_id = \Drupal::request()->headers->get('Client-Id');
	//echo $client_id; exit;
	/** @var \Drupal\contentservice\Service\GenericService $service */
	$service = \Drupal::service('contentservice.GenericService');
	$domain_id = $service->getDomainIdFromClientId($client_id);
	\Drupal::logger('DID')->warning('<pre><code>' . print_r($domain_id, TRUE) . '</code></pre>');
    $entities = \Drupal::entityQuery('node')
				->condition('status', 1)
				->condition('type', 'news')
				->condition('langcode','en')
				->condition('field_domain_access' ,$domain_id)
				->sort('nid' , 'DESC')
				->accessCheck(false)   
				->execute();
    if(!empty($entities)){
        $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entities);
        
        foreach ($nodes as $key => $entity) {
            $result['data'][] = [
				"id"=>  $entity->id(),
				"uuid"=>  $entity->uuid(),
				"user"=>  $entity->uid->target_id,
				"title"=>  $entity->title->value,
				"body"=>  $entity->field_news_description->value,
				"category"=>  $entity->field_news_categories->value,
				"banner"=>  $entity->field_news_banner_url->value,
				"trending"=>  $entity->field_trending_news->value,
				"tags"=>  $entity->field_news_tags->value,
				"date"=>  $entity->changed->value,
				"status"=>  $entity->status->value,
				//"published"=>  $entity->moderation_state->value,
				
				];
		}
     
 	}
	  if(empty($result)) {$result = [];}
	  $response = new ResourceResponse($result);
      $response->addCacheableDependency($result);

   
  	return $response;

  }

}

