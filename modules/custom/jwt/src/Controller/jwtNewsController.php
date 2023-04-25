<?php

namespace Drupal\jwt\Controller;

use Drupal\Core\Controller\ControllerBase;

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
 * An example controller.
 */
class jwtNewsController extends ControllerBase {

  /**
   * Returns a render-able array for a test page.
   */
  public function content() {
	phpinfo();
	exit();
	echo "<pre>";
	$vid = 'deals';
	$terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid, 0, 1);
	foreach($terms as $val) {
		$load_entities = FALSE;
		$child_terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid, $val->tid, $depth, $load_entities);
		$termList = array();
		foreach ($child_terms as $key=>$child_term) {
		  $termList[$child_term->tid] = $child_term->name;
		}
		$all[strtolower(str_replace(" ","_",$val->name))] = $termList;
	}
	print_r($all);exit;
	echo "<pre>";
	print_r($terms);
	
	

	
	
	
	
	
	
	$result = [];
	foreach (\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid) as $item) {
	  $parents = array_reverse(\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadAllParents($item->tid), TRUE);
	  $r = &$result;
	  foreach ($parents as $k => $v) {
		if (isset($r[$k])) {
		  $r[$k] = array_replace($r[$k], [$k => $v->label()]);
		}
		else {
		  $r[$k] = $v->label();
		}
		$r = &$r[$k];
	  }
	}
echo "<pre>";
print_r($result);
	exit;

	
	
	
    
	$response = new ResourceResponse($result);
	$response->addCacheableDependency($result);

   
  	return $response;

  }

}
