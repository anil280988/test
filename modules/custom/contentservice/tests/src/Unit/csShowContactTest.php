<?php

namespace Drupal\contentservice\tests;

use Drupal\contentservice\Plugin\rest\resource\csNews;
use Drupal\contentservice\Plugin\rest\resource\csShowContact;
use Drupal\contentservice\Plugin\rest\resource\csShowProductUpdates;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\contentservice\Service\GenericService;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ServerBag;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\rest\ResourceResponse;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;
use Drupal;

/**
 * Provides unit tests for the csNewsTest Plugin.
 *
 * @coversDefaultClass \Drupal\contentservice\Plugin\rest\resource\csNewsTest
 * @group contentservice
 */
class csShowContactTest extends UnitTestCase
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
   * Test case for Create
   * @return void
   */
  public function testCreate() {

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

    $contact_mock = $this->getMockBuilder(csShowContact::class)
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

    // mocking requrest
    $request_stack = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')
      ->getMock();
    $header_bag = $this->getMockBuilder(HeaderBag::class)
      ->disableOriginalConstructor()
      ->getMock();
    $header_bag->expects($this->at(0))->method('get')
      ->with('Client-Id')
      ->willReturn('test');

    $header_bag->expects(($this->at(1)))->method('get')
      ->with('HOST')
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

    $contact_mock = $this->getMockBuilder(csShowContact::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['get'])
      ->getMock();

    $result = $contact_mock->get();
    $this->assertEquals($access_mock, $result);
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

    $request_stack = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')
      ->getMock();
    $header_bag = $this->getMockBuilder(HeaderBag::class)
      ->disableOriginalConstructor()
      ->getMock();

    $header_bag->expects($this->at(0))->method('get')
      ->with('Client-Id')
      ->willReturn('test');

    $header_bag->expects(($this->at(1)))->method('get')
      ->with('HOST')
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
      ->with('type', 'contact');
    $query_mock->expects($this->at(2))->method('condition')
      ->with('langcode', 'en');
    $query_mock->expects($this->at(3))->method('sort')
      ->with('created', 'DESC');
    $query_mock->expects($this->at(4))->method('accessCheck')
      ->with(false);
    $query_mock->expects($this->at(5))->method('execute')
      ->willReturn(['test_entity_id']);

    $field_uid_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_uid_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['target_id' => 'test_uid']]);

    $field_title_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_title_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'test title']]);

    $field_email_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_email_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'email']]);

    $field_contact_title_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_contact_title_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'contact title']]);

    $field_mobile_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_mobile_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'test mobile']]);

    $field_region_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_region_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'region']]);

    $field_profile_image_url_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_profile_image_url_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'test_url']]);

    // Mocking node Entity
    $node_mock = $this->getMockBuilder('Drupal\node\Entity\Node')
      ->disableOriginalConstructor()
      ->getMock();
    $node_mock->method('id')
      ->willReturn('test_entity_id');

    $node_mock->expects($this->at(1))
      ->method('get')
      ->with('title')
      ->willReturn($field_title_mock);

    $node_mock->expects($this->at(2))
      ->method('get')
      ->with('field_contact_email')
      ->willReturn($field_email_mock);

    $node_mock->expects($this->at(3))
      ->method('get')
      ->with('field_contact_title')
      ->willReturn($field_contact_title_mock);

    $node_mock->expects($this->at(4))
      ->method('get')
      ->with('field_mobile')
      ->willReturn($field_mobile_mock);

    $node_mock->expects($this->at(5))
      ->method('get')
      ->with('field_region')
      ->willReturn($field_region_mock);

    $node_mock->expects($this->at(6))
      ->method('get')
      ->with('field_profile_image_url')
      ->willReturn($field_profile_image_url_mock);

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


    $entity_type_manager->expects($this->at(1))->method('getStorage')
      ->with('node')
      ->willReturn($node_storage);

    $entity_repository = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeRepository')
      ->disableOriginalConstructor()
      ->getMock();

    $entity_repository->method('getEntityTypeFromClass')
      ->with('Drupal\node\Entity\Node')
      ->willReturn('node');

    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
    $container->set('contentservice.GenericService', $service_mock);
    $container->set('request_stack', $request_stack);
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('entity_type.repository', $entity_repository);

    $contact_mock = $this->getMockBuilder(csShowContact::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['get'])
      ->getMock();

    $expected_result = [
      "id" => 'test_entity_id',
      "uid" => 'test_uid',
      "uuid" => 'test_uuid',
      "title" => 'test title',
      "category" => 'test_term_id',
      "description" => 'test_term_id',
      "trending" => 'test_term_id',
      "banner" => 'test_term_id',
      "tags" => 'test_term_id',
      "created" => 1234567,
    ];

    $response = new ResourceResponse($expected_result);
    $response->addCacheableDependency($expected_result);

    $result = $contact_mock->get();
    $this->assertIsObject($result);

  }

  /**
   * Test for post method
   *
   * @return void
   */
  public function testGetWhenRegionOrder() {

    // service
    $service_mock = $this->getMockBuilder(GenericService::class)
      ->disableOriginalConstructor()
      ->getMock();
    $service_mock->method('userDuplicateLoginValidation')
      ->willReturn(1);

    $request_stack = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')
      ->getMock();
    $header_bag = $this->getMockBuilder(HeaderBag::class)
      ->disableOriginalConstructor()
      ->getMock();

    $header_bag->expects($this->at(0))->method('get')
      ->with('Client-Id')
      ->willReturn('test');

    $header_bag->expects(($this->at(1)))->method('get')
      ->with('HOST')
      ->willReturn('us-east-userapi.concierto.cloud');

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
      ->with('type', 'contact');
    $query_mock->expects($this->at(2))->method('condition')
      ->with('langcode', 'en');
    $query_mock->expects($this->at(3))->method('sort')
      ->with('field_region_order', 'ASC');
    $query_mock->expects($this->at(4))->method('accessCheck')
      ->with(false);
    $query_mock->expects($this->at(5))->method('execute')
      ->willReturn(['test_entity_id']);

    $field_uid_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_uid_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['target_id' => 'test_uid']]);

    $field_title_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_title_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'test title']]);

    $field_email_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_email_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'email']]);

    $field_contact_title_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_contact_title_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'contact title']]);

    $field_mobile_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_mobile_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'test mobile']]);

    $field_region_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_region_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'region']]);

    $field_profile_image_url_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_profile_image_url_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'test_url']]);

    // Mocking node Entity
    $node_mock = $this->getMockBuilder('Drupal\node\Entity\Node')
      ->disableOriginalConstructor()
      ->getMock();
    $node_mock->method('id')
      ->willReturn('test_entity_id');

    $node_mock->expects($this->at(1))
      ->method('get')
      ->with('title')
      ->willReturn($field_title_mock);

    $node_mock->expects($this->at(2))
      ->method('get')
      ->with('field_contact_email')
      ->willReturn($field_email_mock);

    $node_mock->expects($this->at(3))
      ->method('get')
      ->with('field_contact_title')
      ->willReturn($field_contact_title_mock);

    $node_mock->expects($this->at(4))
      ->method('get')
      ->with('field_mobile')
      ->willReturn($field_mobile_mock);

    $node_mock->expects($this->at(5))
      ->method('get')
      ->with('field_region')
      ->willReturn($field_region_mock);

    $node_mock->expects($this->at(6))
      ->method('get')
      ->with('field_profile_image_url')
      ->willReturn($field_profile_image_url_mock);

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


    $entity_type_manager->expects($this->at(1))->method('getStorage')
      ->with('node')
      ->willReturn($node_storage);

    $entity_repository = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeRepository')
      ->disableOriginalConstructor()
      ->getMock();

    $entity_repository->method('getEntityTypeFromClass')
      ->with('Drupal\node\Entity\Node')
      ->willReturn('node');

    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
    $container->set('contentservice.GenericService', $service_mock);
    $container->set('request_stack', $request_stack);
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('entity_type.repository', $entity_repository);

    $contact_mock = $this->getMockBuilder(csShowContact::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['get'])
      ->getMock();

    $expected_result = [
      "id" => 'test_entity_id',
      "uid" => 'test_uid',
      "uuid" => 'test_uuid',
      "title" => 'test title',
      "category" => 'test_term_id',
      "description" => 'test_term_id',
      "trending" => 'test_term_id',
      "banner" => 'test_term_id',
      "tags" => 'test_term_id',
      "created" => 1234567,
    ];

    $response = new ResourceResponse($expected_result);
    $response->addCacheableDependency($expected_result);

    $result = $contact_mock->get();
    $this->assertIsObject($result);

  }

  /**
   * Test for post method
   *
   * @return void
   */
  public function testGetWhenEmptyResult() {

    // service
    $service_mock = $this->getMockBuilder(GenericService::class)
      ->disableOriginalConstructor()
      ->getMock();
    $service_mock->method('userDuplicateLoginValidation')
      ->willReturn(1);

    $request_stack = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')
      ->getMock();
    $header_bag = $this->getMockBuilder(HeaderBag::class)
      ->disableOriginalConstructor()
      ->getMock();

    $header_bag->expects($this->at(0))->method('get')
      ->with('Client-Id')
      ->willReturn('test');

    $header_bag->expects(($this->at(1)))->method('get')
      ->with('HOST')
      ->willReturn('us-east-userapi.concierto.cloud');

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
      ->with('type', 'contact');
    $query_mock->expects($this->at(2))->method('condition')
      ->with('langcode', 'en');
    $query_mock->expects($this->at(3))->method('sort')
      ->with('field_region_order', 'ASC');
    $query_mock->expects($this->at(4))->method('accessCheck')
      ->with(false);
    $query_mock->expects($this->at(5))->method('execute')
      ->willReturn([]);

    // Mocking node Entity

    $ent_storage = $this->getMockBuilder(EntityStorageInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $ent_storage->method('getQuery')
      ->with('AND')
      ->willReturn($query_mock);

    // Mocking term

    $entity_type_manager = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeManager')
      ->disableOriginalConstructor()
      ->getMock();

    $entity_type_manager->expects($this->at(0))
      ->method('getStorage')
      ->with('node')
      ->willReturn($ent_storage);

    $entity_repository = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeRepository')
      ->disableOriginalConstructor()
      ->getMock();

    $entity_repository->method('getEntityTypeFromClass')
      ->with('Drupal\node\Entity\Node')
      ->willReturn('node');

    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
    $container->set('contentservice.GenericService', $service_mock);
    $container->set('request_stack', $request_stack);
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('entity_type.repository', $entity_repository);

    $contact_mock = $this->getMockBuilder(csShowContact::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['get'])
      ->getMock();

    $expected_result = [];

    $response = new ResourceResponse($expected_result);
    $response->addCacheableDependency($expected_result);

    $result = $contact_mock->get();
    $this->assertIsObject($result);

  }

}
