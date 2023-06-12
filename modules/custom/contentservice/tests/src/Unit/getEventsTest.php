<?php

namespace Drupal\contentservice\tests;

use Drupal\contentservice\Plugin\rest\resource\getEvents;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Database\StatementInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
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
 * @coversDefaultClass \Drupal\contentservice\Plugin\rest\resource\getEvents
 * @group contentservice
 */
class getEventsTest extends UnitTestCase {

  /**
   * configuration.
   *
   * @var array
   *
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
    $showprodupdates_object = new getEvents(
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
    $this->assertInstanceOf(getEvents::class, $showprodupdates_object);
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
    $createnewsc_object = getEvents::create($container, $this->configuration, $this->pluginId, $this->pluginDefinition);
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

    $query_bag = $this->getMockBuilder(ParameterBag::class)
      ->disableOriginalConstructor()
      ->getMock();

    $query_bag->method('all')
      ->willReturn(['start_time' => 'start_time', 'end_time' => 'end_time']);

    $request = Request::createFromGlobals();
    $request->headers = $header_bag;
    $request->query = $query_bag;
    $request_stack->expects($this->any())
      ->method('getCurrentRequest')
      ->willReturn($request);

    $query_mock = $this->getMockBuilder(QueryInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $query_mock->expects($this->at(0))->method('condition')
      ->with('status', 1);
    $query_mock->expects($this->at(1))->method('condition')
      ->with('type', 'event_calendar');
   $query_mock->expects($this->at(2))->method('condition')
      ->with('field_domain_access', 'test');
    $query_mock->expects($this->at(3))->method('condition')
      ->with('field_event_date', 'start_time', '>=');
    $query_mock->expects($this->at(4))->method('condition')
      ->with('field_event_date', 'end_time', '<=');
    $query_mock->expects($this->at(5))->method('execute')
      ->willReturn(['test_entity_id']);

    // Mocking node Entity
    $node_mock = $this->getMockBuilder('Drupal\node\Entity\Node')
      ->disableOriginalConstructor()
      ->getMock();

    $node_mock->method('id')
      ->willReturn('test_entity_id');

    $node_mock->method('getType')
      ->willReturn('test_entity_type');

    $node_mock->method('uuid')
      ->willReturn('test_uuid');

    $field_title_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $field_title_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'test title']]);

    $field_body_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_body_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'test body']]);

    $field_event_category = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_event_category->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'event category']]);

    $field_event_color = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_event_color->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'event color']]);

    $field_event_date = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_event_date->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'event date']]);

    $field_event_location = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_event_location->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'event location']]);


    $node_mock->expects($this->at(1))
      ->method('get')
      ->with('title')
      ->willReturn($field_title_mock);

    $node_mock->expects($this->at(3))
      ->method('get')
      ->with('body')
      ->willReturn($field_body_mock);

    $node_mock->expects($this->at(4))
      ->method('get')
      ->with('field_event_category')
      ->willReturn($field_event_category);

    $node_mock->expects($this->at(5))
      ->method('get')
      ->with('field_event_color_code')
      ->willReturn($field_event_color);

    $node_mock->expects($this->at(6))
      ->method('get')
      ->with('field_event_date')
      ->willReturn($field_event_date);

    $node_mock->expects($this->at(7))
      ->method('get')
      ->with('field_event_location')
      ->willReturn($field_event_location);

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

    // User mocking
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

    $contact_mock = $this->getMockBuilder(getEvents::class)
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

    $query_bag = $this->getMockBuilder(ParameterBag::class)
      ->disableOriginalConstructor()
      ->getMock();

    $query_bag->method('all')
      ->willReturn(['start_time' => 'start_time', 'end_time' => 'end_time']);

    $request = Request::createFromGlobals();
    $request->headers = $header_bag;
    $request->query = $query_bag;
    $request_stack->expects($this->any())
      ->method('getCurrentRequest')
      ->willReturn($request);

    $access_mock = $this->expectException(AccessDeniedHttpException::class);

    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
    $container->set('contentservice.GenericService', $service_mock);
    $container->set('request_stack', $request_stack);

    $contact_mock = $this->getMockBuilder(getEvents::class)
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

    $query_bag = $this->getMockBuilder(ParameterBag::class)
      ->disableOriginalConstructor()
      ->getMock();

    $query_bag->method('all')
      ->willReturn(['start_time' => 'start_time', 'end_time' => 'end_time']);

    $request = Request::createFromGlobals();
    $request->headers = $header_bag;
    $request->query = $query_bag;
    $request_stack->expects($this->any())
      ->method('getCurrentRequest')
      ->willReturn($request);


    $query_mock = $this->getMockBuilder(QueryInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $query_mock->expects($this->at(0))->method('condition')
      ->with('status', 1);
    $query_mock->expects($this->at(1))->method('condition')
      ->with('type', 'event_calendar');
    $query_mock->expects($this->at(2))->method('condition')
      ->with('field_domain_access', 'test');
    $query_mock->expects($this->at(3))->method('condition')
      ->with('field_event_date', 'start_time', '>=');
    $query_mock->expects($this->at(4))->method('condition')
      ->with('field_event_date', 'end_time', '<=');
    $query_mock->expects($this->at(5))->method('execute')
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

    $contact_mock = $this->getMockBuilder(getEvents::class)
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
