<?php

namespace Drupal\contentservice\tests;

use Drupal\contentservice\Plugin\rest\resource\csShowProductUpdates;
use Drupal\contentservice\Service\GenericService;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\ServerBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides unit tests for the csShowProductUpdatesTest Plugin.
 *
 * @coversDefaultClass \Drupal\contentservice\Plugin\rest\resource\csShowProductUpdatesTest
 * @group contentservice
 */
class csShowProductUpdatesTest extends UnitTestCase
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
   * @var LoggerInterface
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
    $this->pluginId = 'showproductUpdate';
    $this->pluginDefinition['title'] = 'show all updates';
    $this->account = $this->getMockBuilder(AccountProxyInterface::class)->getMock();
  }

  /**
   * Tests the constructor of the csCreateBroadcastTest class.
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
    $createbroadcast_object = new csShowProductUpdates(
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
    $this->assertInstanceOf(csShowProductUpdates::class, $createbroadcast_object);
  }
  
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
	$createnewsc_object = csShowProductUpdates::create($container, $this->configuration,$this->pluginId, $this->pluginDefinition);
	  
    $this->assertTrue(TRUE);
  }

  /**
   * Test for post method
   *
   * @return void
   */
  public function testGet() {

    // service
    $service_mock = $this->getMockBuilder(GenericService::class)
      ->disableOriginalConstructor()
      ->getMock();
    $service_mock->method('userDuplicateLoginValidation')
      ->willReturn(1);
    $service_mock->method('UserPermissionCheck')
      ->with('product_updates', 'show')
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

    $query_mock = $this->getMockBuilder(QueryInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $query_mock->expects($this->at(0))->method('condition')
      ->with('status', 1);
    $query_mock->expects($this->at(1))->method('condition')
      ->with('type', 'product_updates');
    $query_mock->expects($this->at(2))->method('condition')
      ->with('langcode', 'en');
    $query_mock->expects($this->at(3))->method('condition')
      ->with('field_domain_access', 'test');
    $query_mock->expects($this->at(4))->method('accessCheck')
      ->with(false);
    $query_mock->expects($this->at(5))->method('sort')
      ->with('nid', 'DESC');
    $query_mock->expects($this->at(6))->method('execute')
      ->willReturn(['test_entity_id']);

    // Mocking node Entity
    $node_mock = $this->getMockBuilder('Drupal\node\Entity\Node')
      ->disableOriginalConstructor()
      ->getMock();
    $node_mock->method('id')
      ->willReturn('test_entity_id');

    $field_product_status_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_product_status_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['target_id' => 'test_term_id']]);

    $node_mock->expects($this->at(0))
      ->method('get')
      ->with('field_product_status')
      ->willReturn($field_product_status_mock);

    $node_mock->expects($this->at(1))
      ->method('get')
      ->with('field_product_status')
      ->willReturn($field_product_status_mock);


    $field_body_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_body_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'test body']]);

    $node_mock->expects($this->at(2))
      ->method('get')
      ->with('body')
      ->willReturn($field_body_mock);


    $field_uid_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_uid_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['target_id' => 'test_uid']]);

    $node_mock->expects($this->at(4))
      ->method('get')
      ->with('uid')
      ->willReturn($field_uid_mock);

    $field_title_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_title_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['target_id' => 'test title']]);

    $node_mock->expects($this->at(5))
      ->method('get')
      ->with('title')
      ->willReturn($field_title_mock);

    $field_created_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_created_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 1234567]]);

    $node_mock->expects($this->at(6))
      ->method('get')
      ->with('created')
      ->willReturn($field_created_mock);

    $node_storage = $this->getMockBuilder('Drupal\node\NodeStorage')
      ->disableOriginalConstructor()
      ->getMock();

    $ent_storage = $this->getMockBuilder(EntityStorageInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $ent_storage->method('getQuery')
      ->with('AND')
      ->willReturn($query_mock);
    $node_storage->method('loadMultiple')
      ->with(['test_entity_id'])
      ->willReturn([$node_mock]);

    // Mocking term
    $term = $this->getMockBuilder('Drupal\taxonomy\Entity\Term')
      ->disableOriginalConstructor()
      ->getMock();

    $term->method('label')
      ->willReturn('test');

    $term->method('id')
      ->willReturn('test_term_id');

    $field_name_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_name_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'test_name']]);

    $term->expects($this->at(0))
      ->method('get')
      ->with('name')
      ->willReturn($field_name_mock);

    $field_description_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_description_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'test_description']]);

    $term->expects($this->at(0))
      ->method('get')
      ->with('name')
      ->willReturn($field_name_mock);
    $term->expects($this->at(1))
      ->method('get')
      ->with('description')
      ->willReturn($field_name_mock);


    $term_storage = $this->getMockBuilder('Drupal\taxonomy\TermStorage')
      ->disableOriginalConstructor()
      ->getMock();
    $term_storage->method('load')
      ->with('test_term_id')
      ->willReturn($term);

    $entity_type_manager = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeManager')
      ->disableOriginalConstructor()
      ->getMock();
    $entity_type_manager->expects($this->at(0))
      ->method('getStorage')
      ->with('node')
      ->willReturn($ent_storage);

    $entity_type_manager->expects($this->at(2))
      ->method('getStorage')
      ->with('taxonomy_term')
      ->willReturn($term_storage);

    $entity_type_manager->expects($this->at(1))
      ->method('getStorage')
      ->with('node')
      ->willReturn($node_storage);
    $entity_repository = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeRepository')
      ->disableOriginalConstructor()
      ->getMock();

    $entity_repository->expects(($this->at(0)))->method('getEntityTypeFromClass')
      ->with('Drupal\taxonomy\Entity\Term')
      ->willReturn('taxonomy_term');


    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
    $container->set('contentservice.GenericService', $service_mock);
    $container->set('request_stack', $request_stack);
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('entity_type.repository', $entity_repository);

    $contact_mock = $this->getMockBuilder(csShowProductUpdates::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['get'])
      ->getMock();

    $expected_result = [];

    $response = new ResourceResponse($expected_result);
    $response->addCacheableDependency($expected_result);

    $result = $contact_mock->get();
    $this->assertIsObject($result);

  }

  /**
   * Test case: when product status target id is empty
   * @return void
   */
  public function testGetWhenTargetIdIsEmpty() {

    // service
    $service_mock = $this->getMockBuilder(GenericService::class)
      ->disableOriginalConstructor()
      ->getMock();
    $service_mock->method('userDuplicateLoginValidation')
      ->willReturn(1);
    $service_mock->method('UserPermissionCheck')
      ->with('product_updates', 'show')
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

    $query_mock = $this->getMockBuilder(QueryInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $query_mock->expects($this->at(0))->method('condition')
      ->with('status', 1);
    $query_mock->expects($this->at(1))->method('condition')
      ->with('type', 'product_updates');
    $query_mock->expects($this->at(2))->method('condition')
      ->with('langcode', 'en');
    $query_mock->expects($this->at(3))->method('condition')
      ->with('field_domain_access', 'test');
    $query_mock->expects($this->at(4))->method('accessCheck')
      ->with(false);
    $query_mock->expects($this->at(5))->method('sort')
      ->with('nid', 'DESC');
    $query_mock->expects($this->at(6))->method('execute')
      ->willReturn(['test_entity_id']);

    // Mocking node Entity
    $node_mock = $this->getMockBuilder('Drupal\node\Entity\Node')
      ->disableOriginalConstructor()
      ->getMock();
    $node_mock->method('id')
      ->willReturn('test_entity_id');

    $field_product_status_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_product_status_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['target_id' => '']]);

    $node_mock->expects($this->at(0))
      ->method('get')
      ->with('field_product_status')
      ->willReturn($field_product_status_mock);


    $field_body_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_body_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'test body']]);

    $node_mock->expects($this->at(1))
      ->method('get')
      ->with('body')
      ->willReturn($field_body_mock);


    $field_uid_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_uid_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['target_id' => 'test_uid']]);

    $node_mock->expects($this->at(3))
      ->method('get')
      ->with('uid')
      ->willReturn($field_uid_mock);

    $field_title_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_title_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['target_id' => 'test title']]);

    $node_mock->expects($this->at(4))
      ->method('get')
      ->with('title')
      ->willReturn($field_title_mock);

    $field_created_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_created_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 1234567]]);

    $node_mock->expects($this->at(5))
      ->method('get')
      ->with('created')
      ->willReturn($field_created_mock);

    $node_storage = $this->getMockBuilder('Drupal\node\NodeStorage')
      ->disableOriginalConstructor()
      ->getMock();

    $ent_storage = $this->getMockBuilder(EntityStorageInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $ent_storage->method('getQuery')
      ->with('AND')
      ->willReturn($query_mock);
    $node_storage->method('loadMultiple')
      ->with(['test_entity_id'])
      ->willReturn([$node_mock]);

    // Mocking term
    $entity_type_manager = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeManager')
      ->disableOriginalConstructor()
      ->getMock();
    $entity_type_manager->expects($this->at(0))
      ->method('getStorage')
      ->with('node')
      ->willReturn($ent_storage);

    $entity_type_manager->expects($this->at(1))
      ->method('getStorage')
      ->with('node')
      ->willReturn($node_storage);
    $entity_repository = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeRepository')
      ->disableOriginalConstructor()
      ->getMock();

    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
    $container->set('contentservice.GenericService', $service_mock);
    $container->set('request_stack', $request_stack);
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('entity_type.repository', $entity_repository);

    $contact_mock = $this->getMockBuilder(csShowProductUpdates::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['get'])
      ->getMock();

    $expected_result = [];

    $response = new ResourceResponse($expected_result);
    $response->addCacheableDependency($expected_result);

    $result = $contact_mock->get();
    $this->assertIsObject($result);

  }

  /**
   * Test case: when product status target id is empty
   * @return void
   */
  public function testGetWhenEntitiesIsEmpty() {

    // service
    $service_mock = $this->getMockBuilder(GenericService::class)
      ->disableOriginalConstructor()
      ->getMock();
    $service_mock->method('userDuplicateLoginValidation')
      ->willReturn(1);
    $service_mock->method('UserPermissionCheck')
      ->with('product_updates', 'show')
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

    $query_mock = $this->getMockBuilder(QueryInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $query_mock->expects($this->at(0))->method('condition')
      ->with('status', 1);
    $query_mock->expects($this->at(1))->method('condition')
      ->with('type', 'product_updates');
    $query_mock->expects($this->at(2))->method('condition')
      ->with('langcode', 'en');
    $query_mock->expects($this->at(3))->method('condition')
      ->with('field_domain_access', 'test');
    $query_mock->expects($this->at(4))->method('accessCheck')
      ->with(false);
    $query_mock->expects($this->at(5))->method('sort')
      ->with('nid', 'DESC');
    $query_mock->expects($this->at(6))->method('execute')
      ->willReturn(['test_entity_id']);

    $node_storage = $this->getMockBuilder('Drupal\node\NodeStorage')
      ->disableOriginalConstructor()
      ->getMock();

    $ent_storage = $this->getMockBuilder(EntityStorageInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $ent_storage->method('getQuery')
      ->with('AND')
      ->willReturn($query_mock);
    $node_storage->method('loadMultiple')
      ->with(['test_entity_id'])
      ->willReturn([]);

    $entity_type_manager = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeManager')
      ->disableOriginalConstructor()
      ->getMock();
    $entity_type_manager->expects($this->at(0))
      ->method('getStorage')
      ->with('node')
      ->willReturn($ent_storage);

    $entity_type_manager->expects($this->at(1))
      ->method('getStorage')
      ->with('node')
      ->willReturn($node_storage);
    $entity_repository = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeRepository')
      ->disableOriginalConstructor()
      ->getMock();

    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
    $container->set('contentservice.GenericService', $service_mock);
    $container->set('request_stack', $request_stack);
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('entity_type.repository', $entity_repository);

    $contact_mock = $this->getMockBuilder(csShowProductUpdates::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['get'])
      ->getMock();

    $expected_result = [];

    $response = new ResourceResponse($expected_result);
    $response->addCacheableDependency($expected_result);

    $result = $contact_mock->get();
    $this->assertIsObject($result);

  }

  /**
   * Test case when Permission Denied
   *
   * @return void
   */
  public function testPostWhenInvalidLogin()
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
      ->with('product_updates', 'show')
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

    $contact_mock = $this->getMockBuilder(csShowProductUpdates::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['get'])
      ->getMock();

    $result = $contact_mock->get();
    $this->assertEquals($access_mock, $result);
  }

}
