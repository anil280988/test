<?php

namespace Drupal\survey\Controller;

use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Render\Markup;
use Drupal\node\Entity\Node;

/**
 * Class SurveyViewController to list Survey results.
 *
 * @package Drupal\survey\Controller
 */
class SurveyViewController extends ControllerBase {
  
	/**
	* Creates table with pagination showing Survey Results.
	*/
	public function view() {
		// Prepare _sortable_ table header
		$header = array(
		  array('data' => t('ID'), 'field' => 'id'),
          array('data' => t('User EmailID'), 'field' => 'user_email'),
          array('data' => t('Incident ID'), 'field' => 'incidentID'),
          array('data' => t('Result'), 'field' => 'survey_data'),
          array('data' => t('Submitted On'), 'field' => 'submitted_on', 'sort' => 'desc'),
		);
		
		$database = \Drupal::database();
		$query = $database->select('survey_listing_table','slt');
        $query->fields('slt', array('id','user_email', 'incidentID','survey_data','submitted_on'));
        $table_sort = $query->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($header);
        $pager = $table_sort->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(50);
        $result = $pager->execute();
		foreach($result as $outputData) {
			//Alter the serialized form data.
			$surveyData = json_decode($outputData->survey_data);
			$surveyResult = '';
			foreach($surveyData as $records) {
			  $resultData = explode("###",$records);
			  $nodeQuestion = Node::load($resultData[0]);
			  $question = $nodeQuestion->field_question->value; //warning check
			  $option = $resultData[1];
			  $surveyResult .= "<strong>Question: </strong>".$question  . "  <strong>Answer: </strong>".$option."</br>";
			}
			//Output
		    $rows[] = [
			  'id' => $outputData->id,
			  'user_email' => $outputData->user_email,
			  'incidentID' => $outputData->incidentID,
			  'survey_data' => Markup::create($surveyResult),
			  'submitted_on' => $outputData->submitted_on,
		    ];
		}
		
		$build['survey_listing_table'] = array(
          '#theme' => 'table', '#header' => $header,
          '#rows' => $rows
		);
		$build['pager'] = array(
          '#type' => 'pager'
		);
	  
		return $build;
    }
}