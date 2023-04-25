<?php

namespace Drupal\mspclientdomain\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Psr\Log\LoggerInterface;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\user\Entity\Role;
use Drupal\core\modules\taxonomy;
use Drupal\taxonomy\Entity\Term;
use \Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\jwt\Transcoder\JwtTranscoder;
use Drupal\Component\Utility\Crypt;
use \Firebase\JWT\JWT;
use \Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\domain\DomainInterface;
use Drupal\contentservice\Base64Image;
use Drupal\contentservice\GenericService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\paragraphs\Entity\Paragraph;

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
     * @param $node_type
     * @param $data
     * @return \Drupal\rest\ResourceResponse Throws exception expected.
     * Throws exception expected.
     */
    public function get() {
		$client_id = \Drupal::request()->headers->get('Client-Id');
		/** @var \Drupal\contentservice\Service\GenericService $service */
		$service = \Drupal::service('contentservice.GenericService');
		$domain_id = $service->getDomainIdFromClientId($client_id); 
		//Permission Check for the users
		//$has_content_permission =  $service->UserPermissionCheck('menu','create'); //allow
		if($has_content_permission='Allow') {
			
		$entities = \Drupal::entityQuery('node')
        ->condition('status', 1)
        ->condition('type', 'menu_list')
        ->condition('langcode', 'en')
		->condition('field_domain_access', $domain_id)
		->accessCheck(false)
		->sort('created', 'DESC')
		->range(0, 1)
        ->execute();
     
        if(!empty($entities)){
  
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
							//echo $paragraph_target_id;
							$paragraph = Paragraph::load($paragraph_target_id);	
							$paragraph_submenu_items = $paragraph->field_submenu->getValue();
							$submenu_ids = array_column($paragraph_submenu_items, 'target_id');
							
							if(is_array($submenu_ids)) {
								//echo "111";
							$submenuArray =[];
							/** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
							foreach ($submenu_ids as $paragraph_sub_target_id) {
								if(is_numeric($paragraph_sub_target_id)) {
									
							$paragraph_sm = Paragraph::load($paragraph_sub_target_id);
 							
							if(is_object($paragraph_sm)) {
							//  print_r($paragraph_sm); 
							 
								$submenu = [
								"title"=>$paragraph_sm->field_submenu_title->value ,
								"url"=>$paragraph_sm->field_subtitle_link->value ,
								];
								//print_r($submenu); exit;
								 array_push($submenuArray,$submenu);
							    // print_r($submenuArray);
							}
							
							}}
							}
							if(!empty($submenuArray)){
								$result[] = [
								"title"=>$paragraph->field_menu_title->value,
								//"url"=>$paragraph->field_menu_link->value ,
								"childs"=>$submenuArray
								];
							} else {
								$result[] = [
								"title"=>$paragraph->field_menu_title->value,
								"url"=>$paragraph->field_menu_link->value ,
								//"childs"=>$submenuArray
								];
							}
							
								
						}
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
		else 
		{
			$message="Access Deny";
			throw new AccessDeniedHttpException($message);
		}
	}
	
	public function UserPermissionCheck($module,$operation){
		
	$user =\Drupal::currentUser()->getAccount();
	$user_roles =$user->getRoles();
	if(in_array("authenticated",$user_roles)){
		 $user_role = 'authenticated';
		 }
		 if(in_array("content_editor",$user_roles)){
			$user_role = 'content_editor';
		 }
		 if(in_array("content_reviewer",$user_roles)){
			 $user_role = 'content_reviewer';
		 }
		 if(in_array("site_admin",$user_roles)){
		   $user_role = 'site_admin';
		 }  
	
    $entities = \Drupal::entityQuery('node')
	->condition('title', $user_role)
	->condition('field_module_name',$module)
    ->condition('status', 1)
    ->condition('type', 'custom_permissions')
    ->execute();
	
    if(!empty($entities)){
		$nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entities);
		foreach ($nodes as $key => $entity) {
		$pem= $entity->field_create->value;
		}
     
 	  }
	  if($pem==1) { $found_key='Allow';} else {$found_key='Deny';}
	   
	   return $found_key;
		
	} 
	

}