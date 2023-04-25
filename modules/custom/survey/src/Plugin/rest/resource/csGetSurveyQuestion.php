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

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "concierto_cs_all_survey_question",
 *   label = @Translation("concierto cs get survey question"),
 *  serialization_class = "",
 *   uri_paths = {
 *      "canonical" = "/api/csGetSurveyQuestion/{nid}",
 *   }
 * )
 */
class csGetSurveyQuestion extends ResourceBase {

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
    public function get($nid) { 
		//Set Domain - 
		$client_id = \Drupal::request()->headers->get('Client-Id');
		/** @var \Drupal\contentservice\Service\GenericService $service */
		$service = \Drupal::service('contentservice.GenericService');
		$domain_id = $service->getDomainIdFromClientId($client_id);
		if(empty($domain_id)) {
			$message="Invalid MSP, Please contact Administrator";
			throw new AccessDeniedHttpException($message);	
		}
	if(is_numeric($nid)) { 
		
	    $entities = \Drupal::entityQuery('node')
				->condition('status', 1)
				->condition('type', 'survey_questions')
				->condition('langcode','en')
			    ->condition('nid', $nid)
				->sort('created', 'DESC')
				->condition('field_domain_access' ,$domain_id)
				->accessCheck(false)   
				->execute();
		
    if(!empty($entities)){
		$nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entities);
		foreach ($nodes as $key => $entity) {
			$result['data'][] = [
				"id" => $entity->id(),	
				"QuestionType" => $entity->field_survey_type->target_id,
				"questions" => $entity->field_question->value,
				
				"options" => $entity->get('field_options')->target_id,
				
				"surveyType" =>$entity->get('field_survey_type')->target_id,	
				
				];
		}
     
 	  }
	} else if($nid == 'all') {
		$entities = \Drupal::entityQuery('node')
					->condition('status', 1)
					->condition('type', 'survey_questions')
					->condition('langcode','en')
					->sort('created', 'DESC')
					->condition('field_domain_access' ,$domain_id)
					->accessCheck(false)   
					->execute();
			
		if(!empty($entities)){
			$nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entities);
			foreach ($nodes as $key => $entity) {
			    $result['data'][] = [
					"id" => $entity->id(),	
					"clientName" => $entity->field_client_name->value,
					"cloudProviders" => $entity->field_cd_provider->value,
					
					//"customerHeadquarter" => $entity->get('field_cust_hq')->target_id,
					"infraLocation" => $entity->get('field_infra_location')->target_id,
					"industry" =>$entity->get('field_industry')->target_id,
					
					
					"noOfVMs" => $entity->field_no_of_vms->value,
					"o365" => $entity->field_o365->value,
					"requiredServices" => $entity->field_required_services->value,
					"revenue" => $entity->field_revenue->value,
					"status" => $entity->field_status->value,
					"expectedNoOfTickets" => $entity->field_expected_no_of_tickets->value,
					"expectedAvailabilityDate" => $entity->field_expected_availability_date->value,
					"requiredOnboardingDate"=>$entity->field_required_onboarding_date->value,	
				];
				$result['data']['contactInfo'][] = [
					"email" => $entity->field_email->value,
					"mobile" => $entity->field_mobile_number->value,
					"tenantAdminName" => $entity->field_name->value,
				];
			}
		}
	}else {}
		if(empty($result)) {
		  $result = [];
		}
		$response = new ResourceResponse($result);
		$response->addCacheableDependency($result);
		return $response; 
	
	}
}
