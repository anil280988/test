<?php

namespace Drupal\contentservice\tests;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Consolidation\OutputFormatters\Formatters\SerializeFormatter;
use Drupal;
use Drupal\contentservice\Plugin\rest\resource\csCreateRequestFeature;
use Drupal\contentservice\Service\GenericService;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ServerBag;
use Symfony\Component\HttpFoundation\Request;
use Drupal\rest\ResourceResponse;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Provides unit tests for the csCreateRequestFeatureTest Plugin.
 *
 * @coversDefaultClass \Drupal\contentservice\Plugin\rest\resource\csCreateRequestFeatureTest
 * @group contentservice
 */
class csCreateRequestFeatureTest extends Drupal\Tests\UnitTestCase {

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
   * Test case for Create
   * @return void
   */
  public function testCreate()
  {

    $container = new ContainerBuilder();

    $logger = $this->getMockBuilder(LoggerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $accountproxy = $this->getMockBuilder(AccountProxyInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $serializer_mock = $this->getMockBuilder(SerializeFormatter::class)
      ->disableOriginalConstructor()
      ->getMock();

    $container->set('logger.factory', $logger);
    $container->set('current_user', $accountproxy);

    Drupal::setContainer($container);

    $contact_mock = $this->getMockBuilder(csCreateRequestFeature::class)
      ->setConstructorArgs([
        [],
        'test_plugin',
        'plugin_defination',
        ['xml', 'json', 'hal_json'],
        $logger,
        $accountproxy
      ])
      ->setMethodsExcept(['create'])
      ->getMock();

    $contact_mock->create($container, [], 'test_plugin', 'plugin_defination');
    $this->assertTrue(TRUE);
  }

/**
   * Test case when Invalid login.
   *
   * @return void
   */
  public function testPostWhenInvalidLogin()
  {
	$data = [
      'title' => 'title',
	  'description' => 'test description',
	  'file' => 'testfile.png',
    ];

    // service
    $service_mock = $this->getMockBuilder(GenericService::class)
      ->disableOriginalConstructor()
      ->getMock();

    $service_mock->method('userDuplicateLoginValidation')
      ->willReturn(0);

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

    $access_mock = $this->expectException(AccessDeniedHttpException::class);

    $container = new ContainerBuilder();
    Drupal::setContainer($container);
    $container->set('contentservice.GenericService', $service_mock);
    $container->set('request_stack', $request_stack);

    $contact_mock = $this->getMockBuilder(csCreateRequestFeature::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['post'])
      ->getMock();

    $result = $contact_mock->post($data);
    $this->assertEquals($access_mock, $result);
  }

  /**
   * Test case when domain is empty
   *
   * @return void
   */
  public function testPostWhenDomainIsEmpty() {

    $data = [
      'title' => 'title',
	  'description' => 'test description',
	  'file' => 'testfile.png',
    ];

    // service
    $service_mock = $this->getMockBuilder(GenericService::class)
      ->disableOriginalConstructor()
      ->getMock();

    $service_mock->method('userDuplicateLoginValidation')
      ->willReturn(1);

    $service_mock->method('UserPermissionCheck')
      ->with('request_a_feature', 'create')
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

    $request = Request::createFromGlobals();
    $request->headers = $header_bag;
    $request_stack->expects($this->any())
      ->method('getCurrentRequest')
      ->willReturn($request);

    $access_mock = $this->expectException(AccessDeniedHttpException::class);

    $container = new ContainerBuilder();
    Drupal::setContainer($container);
    $container->set('contentservice.GenericService', $service_mock);
    $container->set('request_stack', $request_stack);

    $contact_mock = $this->getMockBuilder(csCreateRequestFeature::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['post'])
      ->getMock();

    $result = $contact_mock->post($data);
    $this->assertEquals($access_mock, $result);
  }

  /**
   * Test case when title is Empty
   *
   * @return void
   */
  public function testPostWhenTitleIsEmpty() {

    $data = [
      'title' => '',
	  'description' => 'test description',
	  'file' => 'testfile.png',
    ];

    // service
    $service_mock = $this->getMockBuilder(GenericService::class)
      ->disableOriginalConstructor()
      ->getMock();

	$service_mock->method('userDuplicateLoginValidation')
      ->willReturn(1);

    $service_mock->method('UserPermissionCheck')
      ->with('request_a_feature	', 'create')
      ->willReturn('Allow');

    $service_mock->method('getDomainIdFromClientId')
      ->with('test')
      ->willReturn('test');

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

    $access_mock = $this->expectException(AccessDeniedHttpException::class);

    $container = new ContainerBuilder();
    Drupal::setContainer($container);
    $container->set('contentservice.GenericService', $service_mock);
    $container->set('request_stack', $request_stack);

    $contact_mock = $this->getMockBuilder(csCreateRequestFeature::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['post'])
      ->getMock();

    $result = $contact_mock->post($data);
    $this->assertEquals($access_mock, $result);
  }

  /**
   * Test case when title have html tags.
   *
   * @return void
   */
  public function testPostWhenhtmlTags() {

    $data = [
      'title' => '<p>title</p>',
	  'description' => 'test description',
	  'file' => 'testfile.png',
    ];

    // service
    $service_mock = $this->getMockBuilder(GenericService::class)
      ->disableOriginalConstructor()
      ->getMock();

	$service_mock->method('userDuplicateLoginValidation')
      ->willReturn(1);

    $service_mock->method('UserPermissionCheck')
      ->with('request_a_feature', 'create')
      ->willReturn('Allow');

    $service_mock->method('getDomainIdFromClientId')
      ->with('test')
      ->willReturn('test');

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

    $access_mock = $this->expectException(AccessDeniedHttpException::class);

    $container = new ContainerBuilder();
    Drupal::setContainer($container);
    $container->set('contentservice.GenericService', $service_mock);
    $container->set('request_stack', $request_stack);

    $contact_mock = $this->getMockBuilder(csCreateRequestFeature::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['post'])
      ->getMock();

    $result = $contact_mock->post($data);
    $this->assertEquals($access_mock, $result);
  }

  /**
   * Test case when description have html tags.
   *
   * @return void
   */
  public function testPostWhenhtmlTagsDesc() {

    $data = [
      'title' => 'title',
	  'description' => '<p>test description</p>',
	  'file' => 'testfile.png',
    ];

    // service
    $service_mock = $this->getMockBuilder(GenericService::class)
      ->disableOriginalConstructor()
      ->getMock();
	
	$service_mock->method('userDuplicateLoginValidation')
      ->willReturn(1);

    $service_mock->method('UserPermissionCheck')
      ->with('request_a_feature', 'create')
      ->willReturn('Allow');

    $service_mock->method('getDomainIdFromClientId')
      ->with('test')
      ->willReturn('test');

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

    $access_mock = $this->expectException(AccessDeniedHttpException::class);

    $container = new ContainerBuilder();
    Drupal::setContainer($container);
    $container->set('contentservice.GenericService', $service_mock);
    $container->set('request_stack', $request_stack);

    $contact_mock = $this->getMockBuilder(csCreateRequestFeature::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['post'])
      ->getMock();

    $result = $contact_mock->post($data);
    $this->assertEquals($access_mock, $result);
  }

  /**
   * Test case when file has invalid extension.
   *
   * @return void
   */
  public function testPostInvalidExtension() {

    $data = [
      'title' => 'title',
	  'description' => 'test description',
	  'file' => 'testfile.abc',
    ];

    // service
    $service_mock = $this->getMockBuilder(GenericService::class)
      ->disableOriginalConstructor()
      ->getMock();

	$service_mock->method('userDuplicateLoginValidation')
      ->willReturn(1);

    $service_mock->method('UserPermissionCheck')
      ->with('request_a_feature', 'create')
      ->willReturn('Allow');

    $service_mock->method('getDomainIdFromClientId')
      ->with('test')
      ->willReturn('test');

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

    $access_mock = $this->expectException(AccessDeniedHttpException::class);

    $container = new ContainerBuilder();
    Drupal::setContainer($container);
    $container->set('contentservice.GenericService', $service_mock);
    $container->set('request_stack', $request_stack);

    $contact_mock = $this->getMockBuilder(csCreateRequestFeature::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['post'])
      ->getMock();

    $result = $contact_mock->post($data);
    $this->assertEquals($access_mock, $result);
  }

  /**
   * Test case when file has invalid name.
   *
   * @return void
   */
  public function testPostInvalidName() {

    $data = [
      'title' => 'title',
	  'description' => 'test description',
	  'file' => 'test:file.png',
    ];

    // service
    $service_mock = $this->getMockBuilder(GenericService::class)
      ->disableOriginalConstructor()
      ->getMock();

	$service_mock->method('userDuplicateLoginValidation')
      ->willReturn(1);

    $service_mock->method('UserPermissionCheck')
      ->with('request_a_feature', 'create')
      ->willReturn('Allow');

    $service_mock->method('getDomainIdFromClientId')
      ->with('test')
      ->willReturn('test');

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

    $access_mock = $this->expectException(AccessDeniedHttpException::class);

    $container = new ContainerBuilder();
    Drupal::setContainer($container);
    $container->set('contentservice.GenericService', $service_mock);
    $container->set('request_stack', $request_stack);

    $contact_mock = $this->getMockBuilder(csCreateRequestFeature::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['post'])
      ->getMock();

    $result = $contact_mock->post($data);
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
	  'file' => 'testimage.png'
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
      ->with('request_a_feature', 'create')
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
        'type' => 'request_a_feature',
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

    $contact_mock = $this->getMockBuilder(csCreateRequestFeature::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['post'])
      ->getMock();

    $expected_result['response'] = [
      'status' => 'success',
      'message' => 'Requested Feature is Created Successfully',
    ];
    $data = [
      'title' => 'title',
	  'description' => 'test description',
	  'file' => 'testimage.png'
    ];

    $response = new ResourceResponse($expected_result);
    $response->addCacheableDependency($expected_result);

    $result = $contact_mock->post($data);
    $this->assertIsObject($result);

  }

}
