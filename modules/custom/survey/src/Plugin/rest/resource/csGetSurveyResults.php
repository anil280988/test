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
use Symfony\Component\HttpFoundation\RedirectResponse ;
use Drupal\jwt\Transcoder\JwtTranscoder;
use \Firebase\JWT\JWT;
use \Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\domain\DomainInterface;
use Drupal\contentservice\GenericService;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\Database\Connection;

/**
 * Provides a resource to get survey results.
 *
 * @RestResource(
 *   id = "concierto_cs_all_survey_results",
 *   label = @Translation("concierto cs get survey results"),
 *  serialization_class = "",
 *   uri_paths = {
 *      "canonical" = "/api/csGetSurveyResults/{surveyId}",
        "create" = "/api/csGetSurveyResults",
 *   }
 * )
 */
class csGetSurveyResults extends ResourceBase {

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
	    $toDate = $data['toDate'] ; //'2024-12-30'
		$fromDate = $data['fromDate']; //'2010-09-29'
		$toDate=date_create($toDate);
		date_time_set($toDate,23,60,59);
		$toDate =date_format($toDate,"Y-m-d H:i:s");
        $database = \Drupal::database();
		$resultData=[];
		$query = $database->query("SELECT * FROM survey_listing_table WHERE surveyId = '".$data['surveyID']."' and (submitted_on BETWEEN '".$fromDate."' AND '".$toDate."') ORDER BY id DESC");
		$output = $query->fetchAll();
		$finalRecord = [];
		
		//question data - 
		$surveyEntity = Node::load($data['surveyID']);
		$questions = $surveyEntity->field_questions_list->getValue();
		$question =[];
		 foreach($questions as $nid =>$entity_question) {
			if(empty($entity_question['target_id'])) {
			   continue;
			}
			 $nodeQuestion = Node::load($entity_question['target_id']);
			 $question[$entity_question['target_id']] = $nodeQuestion->field_question->value;
		 }
					
		
		foreach($output as $outputData) {
			$surveyData = json_decode($outputData->survey_data);
			$result = [];
			foreach($surveyData as $records) {
			  $resultData = explode("###",$records);
			  $option = $resultData[1];
			  $result[] = ['question'=>$question[$resultData[0]],'answer'=>$option];
			}
			$surveyResult[] = [
			  'email' => $outputData->user_email,
			  'incidentId' => $outputData->incidentID,
			  'result' => $result,
			  'submittedOn' => $outputData->submitted_on,
		    ];
		}
$response = new ResourceResponse($surveyResult);
		$response->addCacheableDependency($result);
		return $response; 
	
	}
}
