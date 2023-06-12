<?php

namespace Drupal\contentservice\tests;

use Drupal\contentservice\Plugin\rest\resource\csShowallBroadcast;
use Drupal\contentservice\Plugin\rest\resource\getBroadcast;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Database\StatementInterface;
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
use  Psr\Log\LoggerInterface;

use Drupal\Core\Session\AccountProxyInterface;

/**
 * Provides unit tests for the csShowProductUpdatesTest Plugin.
 *
 * @coversDefaultClass \Drupal\contentservice\Plugin\rest\resource\getBroadcast
 * @group contentservice
 */
class getBroadcastTest extends UnitTestCase {

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
    $showprodupdates_object = new getBroadcast(
      $this->configuration,
      $this->pluginId,
      $this->pluginDefinition,
      $this->serializer_formats,
      $logger
    );
    $container = new ContainerBuilder();
    $container->setParameter('serializer.formats', 'serializer.formats');
    // Assert that the object was created successfully and that the dependency was set.
    $this->assertInstanceOf(getBroadcast::class, $showprodupdates_object);
  }

  /**
   * Test case for Create
   *
   * @return void
   */
  public function testCreate() {

    $container = new ContainerBuilder();
    $logger = $this->getMockBuilder('LoggerInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $accountproxy = $this->getMockBuilder(AccountProxyInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    //Set container parameters.
    $container->set('current_user', $accountproxy);
    $container->set('logger.factory', $logger);
    \Drupal::setContainer($container);
    $container->set('serializer.formats', $this->serializer_formats);
    $createnewsc_object = getBroadcast::create($container, $this->configuration, $this->pluginId, $this->pluginDefinition);
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
      ->with('type', 'broadcast');
    $query_mock->expects($this->at(2))->method('condition')
      ->with('langcode', 'en');
    $query_mock->expects($this->at(3))->method('condition')
      ->with('field_domain_access', 'test');
    $query_mock->expects($this->at(4))->method('sort')
      ->with('created', 'DESC');
    $query_mock->expects($this->at(5))->method('accessCheck')
      ->with(FALSE);
    $query_mock->expects($this->at(6))->method('execute')
      ->willReturn(['test_entity_id']);

    // Mocking node Entity
    $node_mock = $this->getMockBuilder('Drupal\node\Entity\Node')
      ->disableOriginalConstructor()
      ->getMock();

    $node_mock->method('id')
      ->willReturn('test_entity_id');

    $node_mock->method('uuid')
      ->willReturn('test_uuid');

    $field_title_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $field_title_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'test title']]);

    $field_created_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $field_created_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 1234567]]);

    $field_uid_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $field_uid_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['target_id' => 'test_user_id']]);

    $node_mock->expects($this->at(1))
      ->method('get')
      ->with('uid')
      ->willReturn($field_uid_mock);

    $node_mock->expects($this->at(4))
      ->method('get')
      ->with('title')
      ->willReturn($field_title_mock);

    $node_mock->expects($this->at(5))
      ->method('get')
      ->with('created')
      ->willReturn($field_created_mock);

    $ent_storage = $this->getMockBuilder(EntityStorageInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $ent_storage->method('getQuery')
      ->with('AND')
      ->willReturn($query_mock);

    $node_storage = $this->getMockBuilder('Drupal\node\NodeStorage')
      ->disableOriginalConstructor()
      ->getMock();

    $node_storage->method('loadMultiple')
      ->with(['test_entity_id'])
      ->willReturn([$node_mock]);


    // Database query mock
    $db_connection = $this->getMockBuilder(Connection::class)
      ->disableOriginalConstructor()
      ->getMock();

    $select_mock = $this->getMockBuilder(SelectInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $statement_mock = $this->getMockBuilder(StatementInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $select_mock->expects($this->at(0))
      ->method('addField')
      ->with('bu', 'id');
    $select_mock->expects($this->at(1))
      ->method('condition')
      ->with('bu.nid', 'test_entity_id');

    $select_mock->expects($this->at(2))
      ->method('condition')
      ->with('bu.uid', 'test_user_id');

    $select_mock->expects($this->at(3))
      ->method('execute')
      ->willReturn($statement_mock);

    $statement_mock->method('fetchAll')
      ->willReturn(['test_data']);

    $db_connection->method('select')
      ->willReturn($select_mock);

    // User mocking
    $user_mock = $this->getMockBuilder('Drupal\user\Entity\User')
      ->disableOriginalConstructor()
      ->getMock();

    $user_mock->method('id')
      ->willReturn('test_user_id');

    $field_name_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $field_name_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'test name --- test name 2']]);

    $user_mock->expects($this->at(0))
      ->method('get')
      ->with('name')
      ->willReturn($field_name_mock);

    $user_storage = $this->getMockBuilder('Drupal\user\UserStorage')
      ->disableOriginalConstructor()
      ->getMock();

    $user_storage->method('load')
      ->with('test_user_id')
      ->willReturn($user_mock);

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

    $entity_type_manager->expects($this->at(2))
      ->method('getStorage')
      ->with('user')
      ->willReturn($user_storage);

    $entity_repository = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeRepository')
      ->disableOriginalConstructor()
      ->getMock();
    $entity_repository->expects($this->at(0))->method('getEntityTypeFromClass')
      ->with('Drupal\user\Entity\User')
      ->willReturn('user');

    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
    $container->set('contentservice.GenericService', $service_mock);
    $container->set('request_stack', $request_stack);
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('entity_type.repository', $entity_repository);
    $container->set('database', $db_connection);

    $contact_mock = $this->getMockBuilder(getBroadcast::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['get'])
      ->getMock();

    $expected_result = [];

    $response = new ResourceResponse($expected_result);
    $response->addCacheableDependency($expected_result);

    $result = $contact_mock->get('test_entity_id');
    $this->assertIsObject($result);

  }
  /**
   * Test for post method
   *
   * @return void
   */
  public function testGetWhenReadIsZero() {

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
      ->with('type', 'broadcast');
    $query_mock->expects($this->at(2))->method('condition')
      ->with('langcode', 'en');
    $query_mock->expects($this->at(3))->method('condition')
      ->with('field_domain_access', 'test');
    $query_mock->expects($this->at(4))->method('sort')
      ->with('created', 'DESC');
    $query_mock->expects($this->at(5))->method('accessCheck')
      ->with(FALSE);
    $query_mock->expects($this->at(6))->method('execute')
      ->willReturn(['test_entity_id']);

    // Mocking node Entity
    $node_mock = $this->getMockBuilder('Drupal\node\Entity\Node')
      ->disableOriginalConstructor()
      ->getMock();

    $node_mock->method('id')
      ->willReturn('test_entity_id');

    $node_mock->method('uuid')
      ->willReturn('test_uuid');

    $field_title_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $field_title_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'test title']]);

    $field_created_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $field_created_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 1234567]]);

    $field_uid_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $field_uid_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['target_id' => 'test_user_id']]);

    $node_mock->expects($this->at(1))
      ->method('get')
      ->with('uid')
      ->willReturn($field_uid_mock);

    $node_mock->expects($this->at(4))
      ->method('get')
      ->with('title')
      ->willReturn($field_title_mock);

    $node_mock->expects($this->at(5))
      ->method('get')
      ->with('created')
      ->willReturn($field_created_mock);

    $ent_storage = $this->getMockBuilder(EntityStorageInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $ent_storage->method('getQuery')
      ->with('AND')
      ->willReturn($query_mock);

    $node_storage = $this->getMockBuilder('Drupal\node\NodeStorage')
      ->disableOriginalConstructor()
      ->getMock();

    $node_storage->method('loadMultiple')
      ->with(['test_entity_id'])
      ->willReturn([$node_mock]);


    // Database query mock
    $db_connection = $this->getMockBuilder(Connection::class)
      ->disableOriginalConstructor()
      ->getMock();

    $select_mock = $this->getMockBuilder(SelectInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $statement_mock = $this->getMockBuilder(StatementInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $select_mock->expects($this->at(0))
      ->method('addField')
      ->with('bu', 'id');
    $select_mock->expects($this->at(1))
      ->method('condition')
      ->with('bu.nid', 'test_entity_id');

    $select_mock->expects($this->at(2))
      ->method('condition')
      ->with('bu.uid', 'test_user_id');

    $select_mock->expects($this->at(3))
      ->method('execute')
      ->willReturn($statement_mock);

    $statement_mock->method('fetchAll')
      ->willReturn([]);

    $db_connection->method('select')
      ->willReturn($select_mock);

    // User mocking
    $user_mock = $this->getMockBuilder('Drupal\user\Entity\User')
      ->disableOriginalConstructor()
      ->getMock();

    $user_mock->method('id')
      ->willReturn('test_user_id');

    $field_name_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $field_name_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'test name --- test name 2']]);

    $user_mock->expects($this->at(0))
      ->method('get')
      ->with('name')
      ->willReturn($field_name_mock);

    $user_storage = $this->getMockBuilder('Drupal\user\UserStorage')
      ->disableOriginalConstructor()
      ->getMock();

    $user_storage->method('load')
      ->with('test_user_id')
      ->willReturn($user_mock);

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

    $entity_type_manager->expects($this->at(2))
      ->method('getStorage')
      ->with('user')
      ->willReturn($user_storage);

    $entity_repository = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeRepository')
      ->disableOriginalConstructor()
      ->getMock();
    $entity_repository->expects($this->at(0))->method('getEntityTypeFromClass')
      ->with('Drupal\user\Entity\User')
      ->willReturn('user');

    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
    $container->set('contentservice.GenericService', $service_mock);
    $container->set('request_stack', $request_stack);
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('entity_type.repository', $entity_repository);
    $container->set('database', $db_connection);

    $contact_mock = $this->getMockBuilder(getBroadcast::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['get'])
      ->getMock();

    $expected_result = [];

    $response = new ResourceResponse($expected_result);
    $response->addCacheableDependency($expected_result);

    $result = $contact_mock->get('test_entity_id');
    $this->assertIsObject($result);

  }


  /**
   * Test case when Permission Denied
   *
   * @return void
   */
  public function testGetWhenInvalidLogin() {
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

    $contact_mock = $this->getMockBuilder(getBroadcast::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['get'])
      ->getMock();

    $result = $contact_mock->get('test_entity_id');
    $this->assertEquals($access_mock, $result);
  }

  /**
   * Test for post method
   *
   * @return void
   */
  public function testGetWhenEmptyEntities() {

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
      ->with('type', 'broadcast');
    $query_mock->expects($this->at(2))->method('condition')
      ->with('langcode', 'en');
    $query_mock->expects($this->at(3))->method('condition')
      ->with('field_domain_access', 'test');
    $query_mock->expects($this->at(4))->method('sort')
      ->with('created', 'DESC');
    $query_mock->expects($this->at(5))->method('accessCheck')
      ->with(FALSE);

    $query_mock->expects($this->at(6))->method('execute')
      ->willReturn(['test_entity_id']);


    $ent_storage = $this->getMockBuilder(EntityStorageInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $ent_storage->method('getQuery')
      ->with('AND')
      ->willReturn($query_mock);

    $node_storage = $this->getMockBuilder('Drupal\node\NodeStorage')
      ->disableOriginalConstructor()
      ->getMock();

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

    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
    $container->set('contentservice.GenericService', $service_mock);
    $container->set('request_stack', $request_stack);
    $container->set('entity_type.manager', $entity_type_manager);

    $contact_mock = $this->getMockBuilder(getBroadcast::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['get'])
      ->getMock();

    $expected_result = [];

    $response = new ResourceResponse($expected_result);
    $response->addCacheableDependency($expected_result);

    $result = $contact_mock->get('test_entity_id');
    $this->assertIsObject($result);

  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->pluginId = 'showproductUpdate';
    $this->pluginDefinition['title'] = 'show all updates';
    $this->account = $this->getMockBuilder(AccountProxyInterface::class)
      ->getMock();
  }

}
