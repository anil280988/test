<?php

namespace Drupal\contentservice\tests;

use Drupal\contentservice\Plugin\rest\resource\csShowTutorials;
use Drupal\contentservice\Plugin\rest\resource\getNews;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\file\Entity\File;
use Drupal\file\FileStorage;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\contentservice\Service\GenericService;
use Symfony\Component\HttpFoundation\HeaderBag;
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
 * @coversDefaultClass \Drupal\contentservice\Plugin\rest\resource\csShowTutorials
 * @group contentservice
 */
class csShowTutorialsTest extends UnitTestCase {

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
  protected $serializer_formats = ['serializer.formats' => 'serializer.formats'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $container = new ContainerBuilder();
    $logger = $this->getMockBuilder('LoggerInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $logger_factory = $this->getMockBuilder(LoggerChannelFactory::class)
      ->disableOriginalConstructor()
      ->getMock();
    $logger_factory->method('get')
      ->with('article')
      ->willReturn($logger);
    $accountproxy = $this->getMockBuilder(AccountProxyInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    //Set container parameters.
    $container->setParameter('serializer.formats', $this->serializer_formats);
    $container->set('logger.factory', $logger_factory);
    $container->set('current_user', $accountproxy);

    \Drupal::setContainer($container);
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
    $logger_factory = $this->getMockBuilder(LoggerChannelFactory::class)
      ->disableOriginalConstructor()
      ->getMock();
    $logger_factory->method('get')
      ->with('article')
      ->willReturn($logger);
    $accountproxy = $this->getMockBuilder(AccountProxyInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    //Set container parameters.
    $container->setParameter('serializer.formats', $this->serializer_formats);
    $container->set('logger.factory', $logger_factory);
    $container->set('current_user', $accountproxy);

    \Drupal::setContainer($container);
    $mock_reult = csShowTutorials::create($container, $this->configuration, $this->pluginId, $this->pluginDefinition);
    $this->assertIsObject($mock_reult);
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
    $service_mock->method('userClientPermissioCheck')
      ->with('test')
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
      ->willReturn(['category' => 'test_category', 'limit' => 'test_limit']);

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
      ->with('type', 'tutorials');
    $query_mock->expects($this->at(2))->method('condition')
      ->with('langcode', 'en');
    $query_mock->expects($this->at(3))->method('condition')
      ->with('field_domain_access', 'test');
    $query_mock->expects($this->at(4))->method('condition')
      ->with('field_tutorial_category', 'test_category');
    $query_mock->expects($this->at(5))->method('range')
      ->with('0', 'test_limit');
    $query_mock->expects($this->at(6))->method('sort')
      ->with('created', 'DESC');
    $query_mock->expects($this->at(7))->method('accessCheck')
      ->with(FALSE);
    $query_mock->expects($this->at(8))->method('execute')
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


    $field_uid = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_uid->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['target_id' => 'test_uid']]);

    $field_title_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_title_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'test title']]);

    $field_tutorial_banner = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_tutorial_banner->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['target_id' => 'test_file_id']]);

    $field_tutorial_category = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_tutorial_category->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'tutorial category']]);

    $field_trending_tutorials = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_trending_tutorials->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'trending tutorial']]);

    $field_tutorial_credits = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_tutorial_credits->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'tutorial credit']]);

    $field_tutorial_link = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_tutorial_link->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'tutorial link']]);

    $field_tutorial_mode = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_tutorial_mode->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'tutorial mode']]);

    $field_tutorial_time = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_tutorial_time->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'tutorial time']]);

    $field_tutorial_type = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_tutorial_type->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'tutorial type']]);
    $field_created = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_created->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => time()]]);


    $node_mock->expects($this->at(0))
      ->method('get')
      ->with('uid')
      ->willReturn($field_uid);
    $node_mock->expects($this->at(1))->method('get')
      ->with('field_tutorial_banner')
      ->willReturn($field_tutorial_banner);
    $node_mock->expects($this->at(2 ))->method('get')
      ->with('field_tutorial_banner')
      ->willReturn($field_tutorial_banner);
    $node_mock->expects($this->at(5))
      ->method('get')
      ->with('title')
      ->willReturn($field_title_mock);
    $node_mock->expects($this->at(6))
      ->method('get')
      ->with('field_trending_tutorials')
      ->willReturn($field_trending_tutorials);
    $node_mock->expects($this->at(7))
      ->method('get')
      ->with('field_tutorial_category')
      ->willReturn($field_tutorial_category);
    $node_mock->expects($this->at(8))
      ->method('get')
      ->with('field_tutorial_credits')
      ->willReturn($field_tutorial_credits);
    $node_mock->expects($this->at(9))
      ->method('get')
      ->with('field_tutorial_link')
      ->willReturn($field_tutorial_link);
    $node_mock->expects($this->at(10))
      ->method('get')
      ->with('field_tutorial_mode')
      ->willReturn($field_tutorial_mode);
    $node_mock->expects($this->at(11))
      ->method('get')
      ->with('field_tutorial_time')
      ->willReturn($field_tutorial_time);
    $node_mock->expects($this->at(12))
      ->method('get')
      ->with('field_tutorial_type')
      ->willReturn($field_tutorial_type);
    $node_mock->expects($this->at(13))
      ->method('get')
      ->with('created')
      ->willReturn($field_created);

    $user_name = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $user_name->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'user name']]);

    $user_mock = $this->getMockBuilder(User::class)
      ->disableOriginalConstructor()
      ->getMock();

    $user_mock->expects($this->any())
      ->method('get')
      ->with('name')
      ->willReturn($user_name);

    $user_storage = $this->getMockBuilder('Drupal\user\UserStorage')
      ->disableOriginalConstructor()
      ->getMock();

    $user_storage->method('load')
      ->with('test_uid')
      ->willReturn($user_mock);

    $file_mock = $this->getMockBuilder(File::class)
      ->disableOriginalConstructor()
      ->getMock();

    $file_mock->method('createFileUrl')
      ->willReturn('/sites/default/files/test-url.pdf');

    $file_mock->method('getFileUri')
      ->willReturn('public:://test-url.pdf');

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

    $file_storage = $this->getMockBuilder(FileStorage::class)
      ->disableOriginalConstructor()
      ->getMock();

    $file_storage->method('load')
      ->with('test_file_id')
      ->willReturn($file_mock);

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

    $entity_type_manager->expects($this->at(2))
      ->method('getStorage')
      ->with('user')
      ->willReturn($user_storage);

    $entity_type_manager->expects($this->at(3))
      ->method('getStorage')
      ->with('file')
      ->willReturn($file_storage);

    $entity_repository = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeRepository')
      ->disableOriginalConstructor()
      ->getMock();
    $entity_repository->expects($this->at(0))->method('getEntityTypeFromClass')
      ->with('Drupal\user\Entity\User')
      ->willReturn('user');
    $entity_repository->expects($this->at(1))->method('getEntityTypeFromClass')
      ->with('Drupal\file\Entity\File')
      ->willReturn('file');

    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
    $container->set('contentservice.GenericService', $service_mock);
    $container->set('request_stack', $request_stack);
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('entity_type.repository', $entity_repository);

    $contact_mock = $this->getMockBuilder(csShowTutorials::class)
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

    $request = Request::createFromGlobals();
    $request->headers = $header_bag;
    $request_stack->expects($this->any())
      ->method('getCurrentRequest')
      ->willReturn($request);

    $access_mock = $this->expectException(AccessDeniedHttpException::class);

    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
    $container->set('contentservice.GenericService', $service_mock);
    $container->set('request_stack', $request_stack);

    $contact_mock = $this->getMockBuilder(csShowTutorials::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['get'])
      ->getMock();

    $result = $contact_mock->get();
    $this->assertEquals($access_mock, $result);
  }

  public function testGetWhenEntityIsEmpty() {

    // service
    $service_mock = $this->getMockBuilder(GenericService::class)
      ->disableOriginalConstructor()
      ->getMock();
    $service_mock->method('userDuplicateLoginValidation')
      ->willReturn(1);
    $service_mock->method('userClientPermissioCheck')
      ->with('test')
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
      ->willReturn(['category' => 'test_category', 'limit' => 'test_limit']);

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
      ->with('type', 'tutorials');
    $query_mock->expects($this->at(2))->method('condition')
      ->with('langcode', 'en');
    $query_mock->expects($this->at(3))->method('condition')
      ->with('field_domain_access', 'test');
    $query_mock->expects($this->at(4))->method('condition')
      ->with('field_tutorial_category', 'test_category');
    $query_mock->expects($this->at(5))->method('range')
      ->with('0', 'test_limit');
    $query_mock->expects($this->at(6))->method('sort')
      ->with('created', 'DESC');
    $query_mock->expects($this->at(7))->method('accessCheck')
      ->with(FALSE);
    $query_mock->expects($this->at(8))->method('execute')
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

    $contact_mock = $this->getMockBuilder(csShowTutorials::class)
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
