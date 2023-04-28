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
 *   id = "concierto_cs_all_survey",
 *   label = @Translation("concierto cs get survey"),
 *  serialization_class = "",
 *   uri_paths = {
 *      "canonical" = "/api/csGetSurvey/{nid}",
 *   }
 * )
 */
class csGetSurvey extends ResourceBase {

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
			$configuration, $plugin_id, $plugin_definition, $container->getParameter('serializer.formats'), $container->get('logger.factory')->get('plusapi'),$container->get('current_user')
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
			->condition('type', 'suvey')
			->condition('langcode','en')
			->condition('nid', $nid)
			->sort('created', 'DESC')
			->condition('field_domain_access' ,$domain_id)
			->accessCheck(false)   
			->execute();
		
		  if(!empty($entities)){
			$nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entities);
			foreach ($nodes as $key => $entity) {
			  $questions = $entity->field_questions_list->getValue();
			  $question =[];
			  foreach($questions as $nid =>$entity_question) {
			    if(empty($entity_question['target_id'])) {
			      continue;
				}
				$nodeQuestion = Node::load($entity_question['target_id']);
				$paraArray =  $nodeQuestion->get('field_options')->getValue(); //survey_questions_options
				$paraIds = array_column($paraArray, 'target_id');
				$option = [];
				foreach ($paraIds as $paragraph_target_id) { 
				  $paragraphOpt = Paragraph::load($paragraph_target_id);	
				  $optionAnsArray = $paragraphOpt->get('field_answer_options')->getValue();
				  $optParaIds = array_column($optionAnsArray, 'value');
				  foreach ($optParaIds as $optParaIdValue) {
					$option[] = [
					  "answer_option" => $optParaIdValue,
					];
				  }
				} 
				$question[]  = [
				  "id" => $nodeQuestion->id(),
				  "isRequired" => ($nodeQuestion->field_show_chart->value == 1) ? true : false,
				  "QuestionType" =>$nodeQuestion->field_question_type->value,
				  "questions" => $nodeQuestion->field_question->value,
				  "options" => $option,
				];
			  }
				 
			  $result['data'][] = [
				"id" => $entity->id(),
                "title" => $entity->getTitle() ,				
				"question" =>  $question,
				"survey" => $entity->field_survey->getValue(),
				"surveyProblem" => $entity->field_survey_problem->value,
				"surveyText" => $entity->field_survey_text->value,
				];
			}
     
 	      }
		} else if($nid == 'all') {
			$entities = \Drupal::entityQuery('node')
			  ->condition('status', 1)
			  ->condition('type', 'suvey')
			  ->condition('langcode','en')
			  ->sort('created', 'DESC')
			  ->condition('field_domain_access' ,$domain_id)
			  ->accessCheck(false)   
			  ->execute();
			
			if(!empty($entities)){
			  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entities);
			  foreach ($nodes as $key => $entity) {
				$questions = $entity->field_questions_list->getValue();
				$question =[];
				foreach($questions as $nid =>$entity_question) {
				  if(empty($entity_question['target_id'])) {
				    continue;
				  }
				  $nodeQuestion = Node::load($entity_question['target_id']);
				  $paraArray =  $nodeQuestion->get('field_options')->getValue(); //survey_questions_options
				  $paraIds = array_column($paraArray, 'target_id');
				  $option = [];
				  foreach ($paraIds as $paragraph_target_id) {
				    $paragraphOpt = Paragraph::load($paragraph_target_id);	
					$optionAnsArray = $paragraphOpt->get('field_answer_options')->getValue();
					$optParaIds = array_column($optionAnsArray, 'value');
					foreach ($optParaIds as $optParaIdValue) {
					  $option[] = [
						"answer_option" => $optParaIdValue,
					  ];
					}
				  }
				  $question[]  = [
					"id" => $nodeQuestion->id(),
					"isRequired" => ($nodeQuestion->field_show_chart->value == 1) ? true : false,
					"QuestionType" =>$nodeQuestion->field_question_type->value,
					"questions" => $nodeQuestion->field_question->value,
					"options" => $option,
				  ];
				}
				 
				$result['data'][] = [
				  "id" => $entity->id(),
                  "title" => $entity->getTitle(),
				  "question" =>  $question,
				  "survey" => $entity->field_survey->getValue(),
				  "surveyProblem" => $entity->field_survey_problem->value,
				  "surveyText" => $entity->field_survey_text->value,
				];
				
			  }
			}
		}
		
		else {}
		if(empty($result)) {
		  $result = [];
		}
		$response = new ResourceResponse($result);
		$response->addCacheableDependency($result);
		return $response; 
	
	}
}
