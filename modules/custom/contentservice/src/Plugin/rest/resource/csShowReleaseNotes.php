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
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\domain\DomainInterface;
use Drupal\rest\ResourceResponse;
use Drupal\taxonomy\Entity\Term;
use \Drupal\file\Entity\File;
use Psr\Log\LoggerInterface;
use Drupal\node\Entity\Node;
use \Firebase\JWT\JWT;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "concierto_cs_show_release_notes",
 *   label = @Translation("concierto cs show release notes"),
 *  serialization_class = "",
 *   uri_paths = {
 *      "canonical" = "/api/csShowReleaseNotes/{id}",
     }
 * )
 */
class csShowReleaseNotes extends ResourceBase {

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
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('article'),
      $container->get('current_user')
    );
  }

  /**
   * Responds to GET Release Notes requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @param $id
   * @return \Drupal\rest\ResourceResponse Throws exception expected.
   * Throws exception expected.
   */
  public function get($id) {
	//check the domain - 
	$client_id = \Drupal::request()->headers->get('Client-Id');
	/** @var \Drupal\contentservice\Service\GenericService $service */
	$service = \Drupal::service('contentservice.GenericService');
	$login = $service->userDuplicateLoginValidation();
	if($login!='1'){
	  $message="Invalid Login";
	  throw new AccessDeniedHttpException($message);
	}
	$domain_id = $service->getDomainIdFromClientId($client_id);
	if(empty($domain_id)) {
	  $message="Invalid MSP, Please contact Administrator";
	  throw new AccessDeniedHttpException($message);	
	}
	if($id == 'all') {
	  $query = \Drupal::entityQuery('node');
	  $query->condition('status', 1);
	  $query->condition('type', 'release_notes');
	  $query->condition('langcode','en');
	  $query->sort('nid' , 'DESC');
	  $query->accessCheck(false); 
	  $entities = $query->execute();
	  if(!empty($entities)){
		$nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entities);
		$customConfig = \Drupal::config('xai.settings');
		$header = $customConfig->get('release_notes_header');
		$icon = $customConfig->get('release_notes_icon');
		$result['header'] = $header;
		$result['icon'] = $icon;
		foreach ($nodes as $key => $nodeEntity) {
		  //Manage Track-
		  $manageTrack =  $nodeEntity->get('field_manage_track')->getValue(); 
		  $paraIds = array_column($manageTrack, 'target_id');
		  foreach ($paraIds as $paragraph_target_id) {
			$paragraphManage = Paragraph::load($paragraph_target_id);
			//Manage paragraph title-
			$manageTower_targetId = $paragraphManage->field_manage_tower->target_id;
			if(empty($manageTower_targetId)) {
			  $manageData[] = [
			  "title"=> "",
			  "Notes"=> "",
			  ];
			  continue;
			}
			$titleName = Term::load($manageTower_targetId)->get('name')->value;
			$pointers = $paragraphManage->get('field_notes')->getValue();
			$paraPointerIds = array_column($pointers, 'target_id');
			$accomplishedLayerDataArray=[];
			
			foreach ($paraPointerIds as $pointerTargetId) { 
			  $pointerManage = Paragraph::load($pointerTargetId);
			  //Manage pointers[Accomplished/Planned points of release notes.]-
			  $accomplishedData = $pointerManage->get('field_accomplished')->getValue();
			  $layaredData = $pointerManage->get('field_layer')->getValue();
			  $accomplishedLayerDataArray[] = [
			  "accomplish"=>$accomplishedData,
			  "layer"=>$layaredData];
			}
			$manageData[] = [
			  "title"=> $titleName,
			  "Notes"=> $accomplishedLayerDataArray,
			];
		  }
		  
		  //Migrate Track-
		  $migrateTrack =  $nodeEntity->get('field_migrate_track')->getValue();
		  $paraMigrateIds = array_column($migrateTrack, 'target_id');
		  foreach ($paraMigrateIds as $migrateTargetId) {
			$migrateManager=Paragraph::load($migrateTargetId);
			$migrateAccomplishedData = $migrateManager->get('field_accomplished')->getValue();
			$migratePlannedData = $migrateManager->get('field_layer')->getValue();
			$migrateData[] = [
			  "Accomplished"=> $migrateAccomplishedData,
			  "Layer"=>$migratePlannedData
			];
		  }
		  
		  //Maximize Track-
		  $maximizeTrack =  $nodeEntity->get('field_maximize_track')->getValue();
		  $paraMaximizeIds = array_column($maximizeTrack, 'target_id');
		  foreach ($paraMaximizeIds as $maximizeTargetId) {
			$maximizeManager=Paragraph::load($maximizeTargetId);
			$maximizeAccomplishedData = $maximizeManager->get('field_accomplished')->getValue();
			$maximizePlannedData = $maximizeManager->get('field_layer')->getValue();
			$maximizeData[] = [
			  "Accomplished"=> $maximizeAccomplishedData,
			  "Layer"=>$maximizePlannedData
			];
		  }
		  
		  $host = \Drupal::request()->getSchemeAndHttpHost();
		  $dateTimeStamp = $nodeEntity->getCreatedTime();				
		  $date = date('m/d/Y',$dateTimeStamp);
		  $result['releases'][] = [
			  "id" => $nodeEntity->id(),
			  "Name" => $nodeEntity->getTitle(),
			  "date" =>$date,
			  "environment" => $host,
			  "Tracks" => [
			    "Manage Track" =>  $manageData, 
			    "Maximize Track" => $migrateData,
			    "Migrate Track" => $maximizeData,
			  ],
		  ];
		}
			
	  }
	  if(empty($result)) {$result = [];}
	  $response = new ResourceResponse($result);
	  $response->addCacheableDependency($result);
	  return $response;
	}
	else if(is_numeric($id)) {
	  $nodeEntity = Node::load($id);
	  if(!is_object($nodeEntity)) {
		$message="Invalid release node id";
		throw new AccessDeniedHttpException($message);
		return new JsonResponse([$message]);
	  }
		
	  //Manage Track-
	  $manageTrack =  $nodeEntity->get('field_manage_track')->getValue(); 
	  $paraIds = array_column($manageTrack, 'target_id');
	  foreach ($paraIds as $paragraph_target_id) {
		$paragraphManage = Paragraph::load($paragraph_target_id);
		//Manage paragraph title-
		$manageTower_targetId = $paragraphManage->field_manage_tower->target_id;
		if(empty($manageTower_targetId)) {
		  $manageData[] = [
			"title"=> $titleName,
			"Notes"=> $accomplishedLayerDataArray,
		  ];
		  continue;
		}
		$titleName = Term::load($manageTower_targetId)->get('name')->value;
		$pointers = $paragraphManage->get('field_notes')->getValue();
		$paraPointerIds = array_column($pointers, 'target_id');
		$accomplishedLayerDataArray=[];
		
		foreach ($paraPointerIds as $pointerTargetId) {
		  $pointerManage = Paragraph::load($pointerTargetId);
		  //Manage pointers[Accomplished/Planned points of release notes.]-
		  $accomplishedData = $pointerManage->get('field_accomplished')->getValue();
		  $layaredData = $pointerManage->get('field_layer')->getValue();
		  $accomplishedLayerDataArray[] = [
		  "accomplish"=>$accomplishedData,
		  "layer"=>$layaredData];
		}
		$manageData[] = [
		  "title"=> $titleName,
		  "Notes"=> $accomplishedLayerDataArray,
		];
	  }
		
	  //Migrate Track-
	  $migrateTrack =  $nodeEntity->get('field_migrate_track')->getValue();
	  $paraMigrateIds = array_column($migrateTrack, 'target_id');
	  foreach ($paraMigrateIds as $migrateTargetId) {
		$migrateManager=Paragraph::load($migrateTargetId);
		$migrateAccomplishedData = $migrateManager->get('field_accomplished')->getValue();
		$migratePlannedData = $migrateManager->get('field_layer')->getValue();
		$migrateData[] = [
		  "Accomplished"=> $migrateAccomplishedData,
		  "Layer"=>$migratePlannedData
		];
	  }
	  
	  //Maximize Track-
	  $maximizeTrack =  $nodeEntity->get('field_maximize_track')->getValue();
	  $paraMaximizeIds = array_column($maximizeTrack, 'target_id');
	  foreach ($paraMaximizeIds as $maximizeTargetId) {
		$maximizeManager=Paragraph::load($maximizeTargetId);
		$maximizeAccomplishedData = $maximizeManager->get('field_accomplished')->getValue();
		$maximizePlannedData = $maximizeManager->get('field_layer')->getValue();
		$maximizeData[] = [
		  "Accomplished"=> $maximizeAccomplishedData,
		  "Layer"=>$maximizePlannedData
		];
	  }
	  $host = \Drupal::request()->getSchemeAndHttpHost();
	  $dateTimeStamp = $nodeEntity->getCreatedTime();			$date = date('m/d/Y',$dateTimeStamp);
	  $result['releases'][] = [
		"id" => $nodeEntity->id(),
		"Name" => $nodeEntity->getTitle(),
		"date" =>$date,
		"Tracks" => [
		  "Manage Track" =>  $manageData, 
		  "Maximize Track" => $migrateData,
		  "Migrate Track" => $maximizeData,
		],
	  ];
	  if(empty($result)) {$result = [];}
	  $response = new ResourceResponse($result);
	  $response->addCacheableDependency($result);
	  return $response;
	} 
	else if($id == 'release_menu') {
	  $vid = 'manage_tower';
	  $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
	  foreach ($terms as $term) {
		$result['manage'][]= [
		  'tid' => $term->tid,
		  'name' => $term->name,
		];
	  }
	  $vid_migrate = 'migrate_track';
	  $terms_migrate = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid_migrate);
	  foreach ($terms_migrate as $term) {
		$result['migrate'][]= [
		  'tid' => $term->tid,
		  'name' => $term->name,
		];
	  }
	  $vid_maximize = 'migrate_track';
	  $terms_max = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid_maximize);
	  foreach ($terms_max as $term) {
		$result['maximize'][]= [
		  'tid' => $term->tid,
		  'name' => $term->name,
		];
	  }		   
	  $response = new ResourceResponse($result);
	  $response->addCacheableDependency($result);
	  return $response;
    }			
  }
  
}