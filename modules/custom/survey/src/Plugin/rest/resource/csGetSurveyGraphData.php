<?php
namespace Drupal\survey\Plugin\rest\resource;

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
use Drupal\taxonomy\Entity\Term;
use \Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\jwt\Transcoder\JwtTranscoder;
use \Firebase\JWT\JWT;
use \Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\domain\DomainInterface;
use Drupal\contentservice\GenericService;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "concierto_survey_graph",
 *   label = @Translation("concierto survey graph"),
 *  serialization_class = "",
 *   uri_paths = {
 *      "canonical" = "/api/csGetSurveyGraphData/{date}",
         "create" = "/api/csGetSurveyGraphData",
 *   }
 * )
 */
class csGetSurveyGraphData extends ResourceBase {

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
     * Responds to POST requests.
     *
     * Returns a list of bundles for specified entity.
     *
     * @param $node_type
     * @param $data
     * @return \Drupal\rest\ResourceResponse Throws exception expected.
     * Throws exception expected.
     */
    public function post($data) {
		//Set Domain - 
		$client_id = \Drupal::request()->headers->get('Client-Id');
		/** @var \Drupal\contentservice\Service\GenericService $service */
		$service = \Drupal::service('contentservice.GenericService');
		$domain_id = $service->getDomainIdFromClientId($client_id);
		if(empty($domain_id)) {
			$message="Invalid MSP, Please contact Administrator";
			throw new AccessDeniedHttpException($message);	
		}
		//$data = json_decode(\Drupal::request()->getContent(), true);
	    if(!empty($data)) { 
		  
		$toDate = $data['toDate']; //'2024-12-30'
		$fromDate = $data['fromDate']; //'2010-09-29'
		 
		/*$fromDate=date_create($fromDate);
		date_time_set($fromDate,23,60,59);
		$fromDate =date_format($fromDate,"Y-m-d H:i:s");*/
		
		$toDate=date_create($toDate);
		date_time_set($toDate,23,60,59);
		$toDate =date_format($toDate,"Y-m-d H:i:s");
		 
		 $surveyId = $data['surveyID'];
		  
        //question data - 
		$surveyEntity = Node::load($surveyId);
		$questions = $surveyEntity->field_questions_list->getValue();
		$question =[];
		$option = [];
		 foreach($questions as $nid =>$entity_question) {
			if(empty($entity_question['target_id'])) {
			   continue;
			}
			$nodeQuestion = Node::load($entity_question['target_id']);
			$question[$entity_question['target_id']] = $nodeQuestion->field_question->value;
			$quesType = $nodeQuestion->field_question_type->value;
			$paraArray =  $nodeQuestion->get('field_options')->getValue(); //survey_questions_options
			$paraIds = array_column($paraArray, 'target_id');
			foreach ($paraIds as $paragraph_target_id) { 
				$paragraphOpt = Paragraph::load($paragraph_target_id);	
				$optionAnsArray = $paragraphOpt->get('field_answer_options')->getValue();
				$optParaIds = array_column($optionAnsArray, 'value');
				foreach ($optParaIds as $optParaIdValue) {
					$option[$entity_question['target_id']][] = $optParaIdValue;
				}
            }
		}
		$database = \Drupal::database();
		$resultData=[];
		
		$query = $database->query("SELECT * FROM survey_listing_table WHERE surveyId = '".$surveyId."' and (submitted_on >= '".$fromDate."' AND submitted_on <='".$toDate."')");
		$output = $query->fetchAll();
		$totalResponseCount= count($output);
		$queryResponse = $database->query("Select questionID,answer,count(answer)as totalcount from survey_question_responses where (submit_date >= '".$fromDate."' AND submit_date <='".$toDate."') and surveyId = '".$surveyId."' group by answer,questionId");
		
		
		$queryResponse = $queryResponse->fetchAll();
        $countResponseValue = [];
		foreach($queryResponse as $tableResponse) {
			$countResponseValue[$tableResponse->questionID][$tableResponse->answer] = $tableResponse->totalcount;
		}
		//$statusData = [];
		foreach($question as $quesId=>$ques) {
			$resultValue =0;
			$resultPercentage = 0;
			$statusData =[];
			foreach($option[$quesId] as $options) {
				
				if(empty($countResponseValue[$quesId][$options] )) {
					$resultValue =0;
					$resultPercentage = 0;
				} else {
					$resultValue = $countResponseValue[$quesId][$options];
					$resultPercentage = round($this->getPercentage($resultValue, $totalResponseCount));
					$resultValue =0;
				}
				$statusData[] = ["name"=>$options, "value"=>$resultPercentage];
			}
			
			$dataQues['total'] =  $totalResponseCount;
				$dataQues['status'] =  $statusData;
				$result[] = [
					"question" =>$ques,
					"feedback" => $dataQues,				
				];
			}
			
			$response = new ResourceResponse($result);
			$response->addCacheableDependency($result);
			return $response;
		}
	
		$response = new ResourceResponse([]);
		$response->addCacheableDependency([]);
		return $response;
	
	}
	
	public function getPercentage($portion, $total) {
		return $percentage = ($portion / $total) * 100;
	} 
}
