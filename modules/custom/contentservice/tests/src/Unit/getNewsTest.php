<?php

namespace Drupal\contentservice\tests;

use Drupal\contentservice\Plugin\rest\resource\getNews;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\file\Entity\File;
use Drupal\file\FileStorage;
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
 * @coversDefaultClass \Drupal\contentservice\Plugin\rest\resource\getNews
 * @group contentservice
 */
class getNewsTest extends UnitTestCase {

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

    $news_mock = $this->getMockBuilder(getNews::class)
      ->setMethodsExcept(['create', '__construct'])
      ->getMock();
    $news_mock->create($container, $this->configuration, $this->pluginId, $this->pluginDefinition);
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
      ->with('type', 'news');
    $query_mock->expects($this->at(2))->method('condition')
      ->with('langcode', 'en');
    $query_mock->expects($this->at(3))->method('condition')
      ->with('field_domain_access', 'test');
    $query_mock->expects($this->at(4))->method('sort')
      ->with('nid', 'DESC');
    $query_mock->expects($this->at(4))->method('sort')
      ->with('nid', 'DESC');
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

    $node_mock->method('getType')
      ->willReturn('test_entity_type');

    $node_mock->method('uuid')
      ->willReturn('test_uuid');

    $field_news_banner = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_news_banner->expects($this->any())
      ->method('isEmpty')
      ->willReturn(FALSE);
    $field_news_banner->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['target_id' => 'test_file_id']]);

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

    $field_news_description = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_news_description->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'test news']]);

    $field_news_categories = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_news_categories->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'news category']]);

    $field_news_banner_url = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_news_banner_url->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'news banner url']]);

    $field_trending_news = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_trending_news->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'trending news']]);

    $field_news_tags = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_news_tags->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'news tags']]);

    $field_changed= $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_changed->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'changed']]);

    $field_status = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_status->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 1]]);

    $field_moderation_state = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_moderation_state->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'saved']]);

    $node_mock->expects($this->at(0))
      ->method('get')
      ->with('field_news_banner')
      ->willReturn($field_news_banner);

    $node_mock->expects($this->at(1))
      ->method('get')
      ->with('field_news_banner')
      ->willReturn($field_news_banner);

    $node_mock->expects($this->at(3))
      ->method('get')
      ->with('uid')
      ->willReturn($field_uid);

    $node_mock->expects($this->at(4))
      ->method('get')
      ->with('title')
      ->willReturn($field_title_mock);

    $node_mock->expects($this->at(5))
      ->method('get')
      ->with('field_news_description')
      ->willReturn($field_news_description);

    $node_mock->expects($this->at(6))
      ->method('get')
      ->with('field_news_categories')
      ->willReturn($field_news_categories);

    $node_mock->expects($this->at(7))
      ->method('get')
      ->with('field_news_banner_url')
      ->willReturn($field_news_banner_url);

    $node_mock->expects($this->at(8))
      ->method('get')
      ->with('field_trending_news')
      ->willReturn($field_trending_news);

    $node_mock->expects($this->at(9))
      ->method('get')
      ->with('field_news_tags')
      ->willReturn($field_news_tags);

    $node_mock->expects($this->at(10))
      ->method('get')
      ->with('changed')
      ->willReturn($field_changed);
    $node_mock->expects($this->at(11))
      ->method('get')
      ->with('status')
      ->willReturn($field_status);
    $node_mock->expects($this->at(12))
      ->method('get')
      ->with('moderation_state')
      ->willReturn($field_moderation_state);

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
      ->with('file')
      ->willReturn($file_storage);

    $entity_repository = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeRepository')
      ->disableOriginalConstructor()
      ->getMock();
    $entity_repository->expects($this->at(0))->method('getEntityTypeFromClass')
      ->with('Drupal\file\Entity\File')
      ->willReturn('file');


    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
    $container->set('contentservice.GenericService', $service_mock);
    $container->set('request_stack', $request_stack);
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('entity_type.repository', $entity_repository);

    $contact_mock = $this->getMockBuilder(getNews::class)
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

    $contact_mock = $this->getMockBuilder(getNews::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['get'])
      ->getMock();

    $result = $contact_mock->get();
    $this->assertEquals($access_mock, $result);
  }

  /**
   * Test case when Permission Denied
   *
   * @return void
   */
  public function testGetWhenDomainIsEmpty() {
    // service
    $service_mock = $this->getMockBuilder(GenericService::class)
      ->disableOriginalConstructor()
      ->getMock();
    $service_mock->method('userDuplicateLoginValidation')
      ->willReturn(1);

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
    \Drupal::setContainer($container);
    $container->set('contentservice.GenericService', $service_mock);
    $container->set('request_stack', $request_stack);

    $contact_mock = $this->getMockBuilder(getNews::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['get'])
      ->getMock();

    $result = $contact_mock->get();
    $this->assertEquals($access_mock, $result);
  }

  /**
   * Test case when Permission Denied
   *
   * @return void
   */
  public function testGetWhenClientPermissionIsEmpty() {
    // service
    $service_mock = $this->getMockBuilder(GenericService::class)
      ->disableOriginalConstructor()
      ->getMock();
    $service_mock->method('userDuplicateLoginValidation')
      ->willReturn(1);

    $service_mock->method('getDomainIdFromClientId')
      ->with('test')
      ->willReturn('test');

    $service_mock->method('userClientPermissioCheck')
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
    \Drupal::setContainer($container);
    $container->set('contentservice.GenericService', $service_mock);
    $container->set('request_stack', $request_stack);

    $contact_mock = $this->getMockBuilder(getNews::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['get'])
      ->getMock();

    $result = $contact_mock->get();
    $this->assertEquals($access_mock, $result);
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
