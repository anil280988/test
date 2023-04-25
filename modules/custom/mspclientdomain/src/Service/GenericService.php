<?php

namespace Drupal\mspclientdomain\Service;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\user\Entity\Role;
use Drupal\taxonomy\Entity\Term;
use Drupal\domain\DomainInterface;
use Drupal\domain_role\Entity\DomainUser;
use Drupal\domain_role\Entity;

//use Drupal\Core\Config\ConfigFactoryInterface;

class GenericService {

  /**
   * The Messenger Service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a ServiceExample object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   A configuration factory instance.
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  //Get Loggedin UserEntity -
  public function getLoggedInUserEntity() {
    $user =\Drupal::currentUser()->getAccount();
    return  $user;
  }
  
 //Get MSP from header -
  public function getDomainIdFromClientId($client_id) {
	  $config = \Drupal::config('xai.settings');
	  $domain_msp_config = $config->get('domain_msp_config');
	  $domain_msp_configArray = (explode('###',$domain_msp_config));
	  
	  $domain_client_list=[];
	  foreach($domain_msp_configArray as $key => $value ) {
		$msp_configArray = (explode(',',$value));
		$domain_client_list[$msp_configArray[0]] = $msp_configArray[1];
	  }
	  return $domain_client_list[$client_id]; 
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
		$pem= $entity->field_update->value;
		}
     
 	  }
	  if($pem==1) { $found_key='Allow';} else {$found_key='Deny';}
	   
	   return $found_key;
		
	}


	
   public function DomainAccessCheck() {
	
	   /** @var \Drupal\domain\Entity\Domain $active */
      $active = \Drupal::service('domain.negotiator')->getActiveDomain();

	  if (empty($active)) {
		$active = \Drupal::entityTypeManager()->getStorage('domain')->loadDefaultDomain();
	  }
	  /*
	  $result['id']=$active->id;
	  $result['name']=$active->name;
	  $result['hostname']=$active->hostname;
	  $result['domain_id']=$active->domain_id;
	  */
	  
     return $active;
  }

      public function getModerationState(){
	    $moderation_state = 'draft';
		$current_user = \Drupal::currentUser();
		$roles = $current_user->getRoles();
		if (in_array('content_editor', $roles)) {
		  $moderation_state = 'draft';
		}
		if (in_array('content_reviewer', $roles)) {
		  $moderation_state = 'review';
		}
        if (in_array('site_admin', $roles)) {
		  $moderation_state = 'published';
		}
		if (in_array('administrator', $roles)) {
		  $moderation_state = 'published';
		 		}
		return $moderation_state;
	}
	

		
	
	
}