<?php

namespace Drupal\contentservice\Service;

use Drupal;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\user\Entity\Role;
use Drupal\taxonomy\Entity\Term;
use Drupal\domain\DomainInterface;
use Drupal\domain_role\Entity\DomainUser;
use Drupal\domain_role\Entity;
use Drupal\paragraphs\Entity\Paragraph;

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
  /*public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }*/

  //Get Loggedin UserEntity -
  public function getLoggedInUserEntity() {
    $user = Drupal::currentUser()->getAccount();
    return $user;
  }

  //Get MSP from header -
  public function getDomainIdFromClientId($client_id){
    $vid = 'domain_client_mapping';
    $terms = Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree($vid);

    foreach ($terms as $term){
      if($term->name == $client_id){
        //return domain
        return \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term->tid)
          ->get('field_domain')
          ->getValue()[0]['value'];
      }
    }
    return NULL;
  }

  public function UserPermissionCheck($module, $operation){
    $user = Drupal::currentUser()->getAccount();
    $user_roles = $user->getRoles();
    if (in_array("authenticated", $user_roles)) {
      $user_role = 'authenticated';
    }
    if (in_array("content_editor", $user_roles)) {
      $user_role = 'content_editor';
    }
    if (in_array("content_reviewer", $user_roles)) {
      $user_role = 'content_reviewer';
    }
    if (in_array("site_admin", $user_roles)) {
      $user_role = 'site_admin';
    }

    $entities = Drupal::entityQuery('node')
      ->condition('title', $user_role)
      ->condition('field_module_name', $module)
      ->condition('status', 1)
      ->condition('type', 'custom_permissions')
      ->execute();

    if (!empty($entities)) {
      $nodes = Drupal::entityTypeManager()
        ->getStorage('node')
        ->loadMultiple($entities);
      foreach ($nodes as $key => $entity) {
        $pem = $entity->field_create->value;
      }

    }
    if ($pem == 1) {
      $found_key = 'Allow';
    }
    else {
      $found_key = 'Deny';
    }

    return $found_key;
 }

  public function userClientPermissioCheck($clientId) {
    $user = Drupal::currentUser()->getAccount();
    if ($user->get('field_clientid')->value != $clientId) {
      return '0';
    }
    else {
      return "1";
    }
  }
  
  //Invalidate first login token -
  public function userDuplicateLoginValidation() {
	if(!is_numeric(\Drupal::currentUser()->id()) && \Drupal::currentUser()->id() ==0){
	  return 0;
	}
	$userEntity = User::load(\Drupal::currentUser()->id());
	$newToken = $userEntity->get('field_jwt_new')->value;
	$bearer = \Drupal::request()->server->get('REDIRECT_HTTP_AUTHORIZATION');
	$bearer = str_replace('Bearer ',"",$bearer);
    if($newToken == $bearer){
		return 1;
	}
	return 0;
  }
}
