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
use Drupal\Component\Utility\Crypt;
use \Firebase\JWT\JWT;
use \Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\domain\DomainInterface;
use Drupal\contentservice\Base64Image;
use Drupal\contentservice\GenericService;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "concierto_cs_create_survey_response",
 *   label = @Translation("concierto cs Create Survey Response"),
 *  serialization_class = "",
 *   uri_paths = {
 *      "canonical" = "/api/csCreateSurveyResponse",
 *     "create" = "/api/csCreateSurveyResponse",
 *   }
 * )
 */
class csCreateSurveyResponse extends ResourceBase {

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
		$client_id = \Drupal::request()->headers->get('Client-Id');
		/** @var \Drupal\contentservice\Service\GenericService $service */
		$service = \Drupal::service('contentservice.GenericService');
		$domain_id = $service->getDomainIdFromClientId($client_id);
		if(empty($domain_id)) {
			$message="Invalid MSP, Please contact Administrator";
			throw new AccessDeniedHttpException($message);	
		}
		$database = \Drupal::database();
		if((!is_numeric($data['surveyID'])) || (empty($data['user_email'])) || (empty($data['incidentID']))
		   || (empty($data['survey_data'])) || (empty($data['status'])) || (empty($data['submitted_on']))) {
		   $message="Invalid Data, Please contact Administrator";
		   throw new AccessDeniedHttpException($message);  
		}
		foreach($data['survey_data'] as $val) {
		if (!stristr($val, '###')){
		$message="Invalid Data format, Please contact Administrator";
		   throw new AccessDeniedHttpException($message);  
		}
		}
		$dateVal = $data['submitted_on'];
		$surveyId = $data['surveyID'];
		$email = $data['user_email'];
		$result = $database->insert('survey_listing_table')
		  ->fields([
			'surveyID' => $data['surveyID'],
			'user_email' => $data['user_email'],
			'clientID' => $client_id,
			'domain' => $domain_id,
			'incidentID' => $data['incidentID'],
			'survey_data' => json_encode($data['survey_data']),
			'status' => $data['status'],
			'submitted_on' => $data['submitted_on']
	    ])
	    ->execute();
		foreach($data['survey_data'] as $records) { 
			$resultData = explode("###",$records);
			$time=strtotime($dateVal);
			$month=date("m",$time);
			$year=date("Y",$time);
			
			$quesentity = Node::load($resultData[0]);
			$showChart = $quesentity->field_show_chart->value;
			if($showChart =='1') {
				$chart = 'Yes';
			} else {
				$chart = 'No';
			}
			
		    $queryData = $database->insert('survey_question_responses')
		  ->fields([
			'surveyID' => $surveyId,
			'clientID' => $client_id,
			'questionID' => $resultData[0],
			'responseID' =>$result,
			'domain' => $domain_id,
			'submit_month' => $month,
			'submit_year' => $year,
			'answer' => $resultData[1],
			'submit_date' =>$dateVal,
			'Show_chart' => $chart,
	    ])
	    ->execute();
		}
		
		$resultOutput['response'] = ['status' => 'success', 'message' => 'Survey Response is Submitted Successfully'
		];

		$response = new ResourceResponse($resultOutput);
		$response->addCacheableDependency($resultOutput);
		return $response; 
   
	}

}
