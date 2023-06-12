<?php

namespace Drupal\contentservice\Plugin\rest\resource;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\contentservice\GenericService;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Drupal\node\Entity\Node;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "concierto_cs_menu",
 *   label = @Translation("concierto cs menu data"),
 *  serialization_class = "",
 *   uri_paths = {
 *      "canonical" = "/api/csMenu",
 *      "create" = "/api/csMenu",
 *   }
 * )
 */
class csDomainMenu extends ResourceBase {

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
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @return \Drupal\rest\ResourceResponse Throws exception expected.
   * Throws exception expected.
   */
  public function get() {
	/** @var \Drupal\contentservice\Service\GenericService $service */
	$service = \Drupal::service('contentservice.GenericService');
	$login = $service->userDuplicateLoginValidation();
	if ($login != '1'){
	  $message = "Invalid Login";
	  throw new AccessDeniedHttpException($message);
	}
	$client_id = \Drupal::request()->headers->get('Client-Id');
	$domain_id = $service->getDomainIdFromClientId($client_id);
	if (empty($domain_id)) {
	  $message = "Invalid Tenant";
	  throw new AccessDeniedHttpException($message);
	}
	$query = \Drupal::entityQuery('node');
	$query->condition('type', 'menu_list');
	$query->condition('langcode', 'en');
	$query->condition('status', 1);
	$query->condition('field_domain_access', $domain_id);
	$query->accessCheck(false);
	$query->sort('created', 'DESC');
	$query->range(0, 1);
	$entities = $query->execute();
	if (!empty($entities)){
	  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entities);
	  foreach ($nodes as $key => $entity) {
		// Get data from field.
		if ($paragraph_field_items = $entity->get('field_menu')->getValue()) {
		  // Get storage. It very useful for loading a small number of objects.
		  $paragraph_storage = \Drupal::entityTypeManager()->getStorage('paragraph');
		  // Collect paragraph field's ids.
		  $ids = array_column($paragraph_field_items, 'target_id');
		  $submenuArray=null;
		  /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
		  foreach ($ids as $paragraph_target_id) {
			if(is_numeric($paragraph_target_id)) {
			  $paragraph = Paragraph::load($paragraph_target_id);	
			  $paragraph_submenu_items = $paragraph->field_submenu->getValue();
			  $submenu_ids = array_column($paragraph_submenu_items, 'target_id');
			  if(is_array($submenu_ids)) {
				$submenuArray =[];
				/** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
				foreach ($submenu_ids as $paragraph_sub_target_id) {
				  if(is_numeric($paragraph_sub_target_id)) {
					$paragraph_sm = Paragraph::load($paragraph_sub_target_id);
					if(is_object($paragraph_sm)) {
					  $submenu = [
						"title"=>$paragraph_sm->field_submenu_title->value ,
						"url"=>$paragraph_sm->field_subtitle_link->value ,
						];
						array_push($submenuArray,$submenu);
					}
				  }
				}
			  }
			  if(!empty($submenuArray)){
				$result[] = [
				  "title"=>$paragraph->field_menu_title->value,
				  "childs"=>$submenuArray
				];
			  } else {
				$result[] = [
				  "title"=>$paragraph->field_menu_title->value,
				  "url"=>$paragraph->field_menu_link->value ,
  				];
			  }
			}
		  }
		}
	    if(empty($result)) {
	      $result = [];
	    }
	    $response = new ResourceResponse($result);
	    $response->addCacheableDependency($result);
	    return $response;	
	  }
	}  else {
	  $message="Access Deny";
	  throw new AccessDeniedHttpException($message);
	}
  }

}