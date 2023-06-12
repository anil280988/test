<?php

namespace Drupal\contentservice\tests;

use Drupal\contentservice\Plugin\rest\resource\csDomainMenu;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\contentservice\Plugin\rest\resource\csContentPermission;
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
class csContentPermissionTest extends UnitTestCase {

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
      ->with('plusapi')
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
      ->with('plusapi')
      ->willReturn($logger);
    $accountproxy = $this->getMockBuilder(AccountProxyInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    //Set container parameters.
    $container->setParameter('serializer.formats', $this->serializer_formats);
    $container->set('logger.factory', $logger_factory);
    $container->set('current_user', $accountproxy);

    \Drupal::setContainer($container);
    $mock_reult = csContentPermission::create($container, $this->configuration, $this->pluginId, $this->pluginDefinition);
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

    $request = Request::createFromGlobals();
    $request->headers = $header_bag;
    $request_stack->expects($this->any())
      ->method('getCurrentRequest')
      ->willReturn($request);

    $query_mock = $this->getMockBuilder(QueryInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $query_mock->expects($this->at(0))->method('condition')
      ->with('type', 'menu_list');
    $query_mock->expects($this->at(1))->method('condition')
      ->with('langcode', 'en');
    $query_mock->expects($this->at(2))->method('condition')
      ->with('status', 1);
    $query_mock->expects($this->at(3))->method('condition')
      ->with('field_domain_access', 'test');
    $query_mock->expects($this->at(4))->method('accessCheck')
      ->with(FALSE);
    $query_mock->expects($this->at(5))->method('sort')
      ->with('created', 'DESC');
    $query_mock->expects($this->at(6))->method('range')
      ->with(0, 1);
    $query_mock->expects($this->at(7))->method('execute')
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

    $field_feature_permission = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_feature_permission->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'test permission']]);

    $node_mock->expects($this->at(0))
      ->method('get')
      ->with('field_feature_permission')
      ->willReturn($field_feature_permission);

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

    $entity_repository = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeRepository')
      ->disableOriginalConstructor()
      ->getMock();
    $entity_repository->method('getEntityTypeFromClass')
      ->with('Drupal\paragraphs\Entity\Paragraph')
      ->willReturn('paragraph');

    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
    $container->set('contentservice.GenericService', $service_mock);
    $container->set('request_stack', $request_stack);
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('entity_type.repository', $entity_repository);

    $contact_mock = $this->getMockBuilder(csContentPermission::class)
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

    $contact_mock = $this->getMockBuilder(csDomainMenu::class)
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
      ->with('type', 'menu_list');
    $query_mock->expects($this->at(1))->method('condition')
      ->with('langcode', 'en');
    $query_mock->expects($this->at(2))->method('condition')
      ->with('status', 1);
    $query_mock->expects($this->at(3))->method('condition')
      ->with('field_domain_access', 'test');
    $query_mock->expects($this->at(4))->method('accessCheck')
      ->with(FALSE);
    $query_mock->expects($this->at(5))->method('sort')
      ->with('created', 'DESC');

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

    $contact_mock = $this->getMockBuilder(csContentPermission::class)
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
