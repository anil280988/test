<?php

namespace Drupal\contentservice\tests;

use Drupal\contentservice\Plugin\rest\resource\csUpdateBroadcast;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\contentservice\Service\GenericService;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ServerBag;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\rest\ResourceResponse;
use Drupal\Tests\UnitTestCase;
use Drupal;

/**
 * Provides unit tests for the csUpdateBroadcastTest Plugin.
 *
 * @coversDefaultClass \Drupal\contentservice\Plugin\rest\resource\csUpdateBroadcastTest
 * @group contentservice
 */
class csUpdateBroadcastTest extends UnitTestCase
{
  /**
   * configuration.
   *
   * @var array
   */
  protected $configuration = [];

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Plugin definition.
   *
   * @var array
   */
  protected $pluginDefinition = [
    'digest_interval' => '1 day',
  ];

  /**
   * Plugin ID.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * serializer_formats.
   *
   * @var array
   */
  protected $serializer_formats = [];

  /**
    * {@inheritdoc}
  */
  protected function setUp(): void {
    parent::setUp();
    $this->pluginId = 'updatebroadcast';
    $this->pluginDefinition['title'] = 'Update Broadcast';
    $this->account = $this->getMockBuilder(AccountProxyInterface::class)->getMock();
  }

  /**
   * Tests the constructor of the csUpdateBroadcastTest class.
   *
   * @constructor ::__construct
   */
  public function testConstructor() {
	// Create a mock object for any dependencies that are required by the constructor.
    $dependency = $this->getMockBuilder(DependencyClass::class)
      ->disableOriginalConstructor()
      ->getMock();
	$logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

    // Call the constructor with the mocked dependency.
    $createbroadcast_object = new csUpdateBroadcast(
	  $this->configuration,
	  $this->pluginId,
	  $this->pluginDefinition,
	  $this->serializer_formats,
	  $logger,
	  $this->account
	);
	$container = new ContainerBuilder();
	$container->setParameter('serializer.formats', 'serializer.formats');
    // Assert that the object was created successfully and that the dependency was set.
    $this->assertInstanceOf(csUpdateBroadcast::class, $createbroadcast_object);
  }

  /**
   * Tests the constructor of the csUpdateBroadcastTest class.
   *
   * @constructor ::__construct
   */

  /**
   * Test case for Create
   * @return void
   */
  public function testCreate() {

    $container = new ContainerBuilder();
    $logger = $this->getMockBuilder('LoggerInterface')->disableOriginalConstructor()->getMock();
    $accountproxy = $this->getMockBuilder(AccountProxyInterface::class)->disableOriginalConstructor()->getMock();
	//Set container parameters.
	$container->set('current_user', $accountproxy);
	$container->set('logger.factory', $logger);
    \Drupal::setContainer($container);
	$container->set('serializer.formats', $this->serializer_formats);
	$createnewsc_object = csUpdateBroadcast::create($container, $this->configuration,$this->pluginId, $this->pluginDefinition);
	  
    $this->assertTrue(TRUE);
  }

  /**
   * Test case when Permission Denied
   *
   * @return void
   */
  public function testGetWhenInvalidLogin()
  {

    $data = [
      'title' => 'title',
      'description' => 'test description',
      'product_type' => 'test product type',
      'category' => '5',
      'type' => 3,
    ];
    // service
    $service_mock = $this->getMockBuilder(GenericService::class)
      ->disableOriginalConstructor()
      ->getMock();

    $service_mock->method('userDuplicateLoginValidation')
      ->willReturn(0);

    $service_mock->method('UserPermissionCheck')
      ->with('Broadcast', 'create')
      ->willReturn('Allow');

    $service_mock->method('getDomainIdFromClientId')
      ->with('test')
      ->willReturn('');

    // mocking requrest
    $request_stack = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')
      ->getMock();
    $header_bag = $this->getMockBuilder(HeaderBag::class)
      ->disableOriginalConstructor()
      ->getMock();
    $header_bag->method('get')
      ->with('Client-Id')
      ->willReturn('test');
    $server_bag = $this->getMockBuilder(ServerBag::class)
      ->disableOriginalConstructor()
      ->getMock();
    $server_bag->method('get')
      ->with('REDIRECT_HTTP_AUTHORIZATION')
      ->willReturn('token');

    $request = Request::createFromGlobals();
    $request->headers = $header_bag;
    $request->server = $server_bag;

    $request_stack->expects($this->any())
      ->method('getCurrentRequest')
      ->willReturn($request);

    $access_mock = $this->expectException(AccessDeniedHttpException::class);

    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
    $container->set('contentservice.GenericService', $service_mock);
    $container->set('request_stack', $request_stack);

    $contact_mock = $this->getMockBuilder(csShowEvent::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['get'])
      ->getMock();

    $result = $contact_mock->get('product_updates');
    $this->assertEquals($access_mock, $result);
  }

    /**
   * Test for post method
   *
   * @return void
   */
  public function testPost() {

    $data = [
      'title' => 'title',
      'description' => 'test description',
      'product_type' => 'test product type',
      'category' => '5',
      'type' => 3,
    ];

    $messanger = $this->getMockBuilder(MessengerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    // service
    $service_mock = $this->getMockBuilder(GenericService::class)
      ->disableOriginalConstructor()
      ->getMock();
	
	$service_mock->method('userDuplicateLoginValidation')
      ->willReturn(1);

    $service_mock->method('UserPermissionCheck')
      ->with('Broadcast', 'create')
      ->willReturn('Allow');

    $service_mock->method('getDomainIdFromClientId')
      ->with('test')
      ->willReturn('test');

    // Mocking config
    $config_factory = $this->getMockBuilder(ConfigFactory::class)
      ->disableOriginalConstructor()
      ->getMock();
    $config = $this->getMockBuilder(Config::class)
      ->disableOriginalConstructor()
      ->getMock();

    $config->method('get')
      ->with('domain_msp_config')
      ->willReturn('test,test3###test,test2');

    $config_factory->method('get')
      ->with('xai.settings')
      ->willReturn($config);

    // mocking requrest
    $request_stack = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')
      ->getMock();
    $header_bag = $this->getMockBuilder(HeaderBag::class)
      ->disableOriginalConstructor()
      ->getMock();
    $header_bag->method('get')
      ->with('Client-Id')
      ->willReturn('test');

    $request = Request::createFromGlobals();
    $request->headers = $header_bag;
    $request_stack->expects($this->any())
      ->method('getCurrentRequest')
      ->willReturn($request);

    // Mocking node Entity
    $node_mock = $this->getMockBuilder('Drupal\node\Entity\Node')
      ->disableOriginalConstructor()
      ->getMock();
    $node_mock->method('id')
      ->willReturn('sample_id');

    $node_storage = $this->getMockBuilder('Drupal\node\NodeStorage')
      ->disableOriginalConstructor()
      ->getMock();
    $node_storage->method('create')
      ->with([
        'type' => 'broadcast',
        'title' => $data['title'],
        'status' => TRUE,
      ])
      ->willReturn($node_mock);

    $time_mock = $this->getMockBuilder(Drupal\Component\Datetime\Time::class)
      ->disableOriginalConstructor()
      ->getMock();

    $node_storage->method('save')
      ->willReturnSelf();

    $entity_repository = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeRepository')
      ->disableOriginalConstructor()
      ->getMock();

    $entity_repository->method('getEntityTypeFromClass')
      ->with('Drupal\node\Entity\Node')
      ->willReturn('node');
    $entity_repository = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeRepository')
      ->disableOriginalConstructor()
      ->getMock();


    $entity_repository->method('getEntityTypeFromClass')
      ->with('Drupal\node\Entity\Node')
      ->willReturn('node');

    $entity_type_manager = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeManager')
      ->disableOriginalConstructor()
      ->getMock();

    $entity_type_manager->expects($this->any())
      ->method('getStorage')
      ->with('node')
      ->willReturn($node_storage);

    $container = new ContainerBuilder();
    Drupal::setContainer($container);
    $container->set('contentservice.GenericService', $service_mock);
    $container->set('request_stack', $request_stack);
    $container->set('config.factory', $config_factory);
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('entity_type.repository', $entity_repository);
    $container->set('datetime.time', $time_mock);

    $contact_mock = $this->getMockBuilder(csCreateBroadcast::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['post'])
      ->getMock();

    $expected_result['response'] = [
      'status' => 'success',
      'message' => 'News is Created Successfully',
    ];
    $expected_result['response']['data'] = [
      'title' => $data['title'],
      'description' => $data['description'],
      'product_type' => $data['product_type'],
      'type' => $data['type'],
      'date' => '987',
    ];

    $response = new ResourceResponse($expected_result);
    $response->addCacheableDependency($expected_result);

    $result = $contact_mock->post($data);
    $this->assertIsObject($result);

  }
}
