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
 *   id = "concierto_incidentStatus",
 *   label = @Translation("concierto incident status"),
 *  serialization_class = "",
 *   uri_paths = {
 *      "canonical" = "/api/csGetIncidentStatus",
        "create" = "/api/csGetIncidentStatus",
 *   }
 * )
 */
class csGetIncidentStatus extends ResourceBase {

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
    public function post() {
		//Set Domain - 
		$client_id = \Drupal::request()->headers->get('Client-Id');
		/** @var \Drupal\contentservice\Service\GenericService $service */
		$service = \Drupal::service('contentservice.GenericService');
		$domain_id = $service->getDomainIdFromClientId($client_id);
		if(empty($domain_id)) {
			$message="Invalid MSP, Please contact Administrator";
			throw new AccessDeniedHttpException($message);	
		}
		$data = json_decode(\Drupal::request()->getContent(), true);
	    if(is_array($data['incident']) && !empty($data['incident'])) { 
		  $database = \Drupal::database();
		  $resultData=[];
		  foreach($data['incident'] as $incidentId) {
			  $query = $database->query("SELECT incidentID FROM survey_listing_table where incidentID='".$incidentId."'");
		      $output = $query->fetchAll(); 
			  if(empty($output)) {
				 
				  $resultData[] = ["ticketId"=>$incidentId, "value"=>"0"];
			  }
		      else {
			      $resultData[] = ["ticketId"=>$incidentId, "value"=>"1"];
		      }
		  }
		  
		  $response = new ResourceResponse($resultData);
		$response->addCacheableDependency($result);
		return $response; 
	
	   
		
   } 
		
		else {}
		//print_r($result);
		if(empty($result)) {
		  $result = [];
		}
		$response = new ResourceResponse($result);
		$response->addCacheableDependency($result);
		return $response; 
	
	}
}
