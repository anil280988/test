<?php

namespace Drupal\contentservice\tests;

use Drupal\contentservice\Plugin\rest\resource\csNews;
use Drupal\contentservice\Plugin\rest\resource\csShowProductCat;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\contentservice\Service\GenericService;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ServerBag;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\HttpFoundation\Request;
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
class csShowProductCatTest extends UnitTestCase
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

    $contact_mock = $this->getMockBuilder(csShowProductCat::class)
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

    $contact_mock = $this->getMockBuilder(csShowProductCat::class)
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
    $header_bag->method('get')
      ->with('Client-Id')
      ->willReturn('test');

    $request = Request::createFromGlobals();
    $request->headers = $header_bag;
    $request_stack->expects($this->any())
      ->method('getCurrentRequest')
      ->willReturn($request);

    // Mocking node Entity
    $term_mock = $this->getMockBuilder('Drupal\taxonomy\Entity\Term')
      ->disableOriginalConstructor()
      ->getMock();

    $term_mock->method('label')
      ->willReturn('test');

    $term_mock->method('id')
      ->willReturn('test_term_id');

    $term_storage = $this->getMockBuilder('Drupal\taxonomy\TermStorage')
      ->disableOriginalConstructor()
      ->getMock();

    $term_storage->expects($this->at(0))->method('loadByProperties')
      ->with(['vid' => 'product_status'])
      ->willReturn(['test_term_id' => 'test_term_id']);

    $term_storage->expects($this->at(1))->method('load')
      ->with('test_term_id')
      ->willReturn($term_mock);
    // Mocking term

    $entity_type_manager = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeManager')
      ->disableOriginalConstructor()
      ->getMock();

    $entity_type_manager->method('getStorage')
      ->with('taxonomy_term')
      ->willReturn($term_storage);

    $entity_repository = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeRepository')
      ->disableOriginalConstructor()
      ->getMock();

    $entity_repository->method('getEntityTypeFromClass')
      ->with('Drupal\taxonomy\Entity\Term')
      ->willReturn('taxonomy_term');

    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
    $container->set('contentservice.GenericService', $service_mock);
    $container->set('request_stack', $request_stack);
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('entity_type.repository', $entity_repository);

    $contact_mock = $this->getMockBuilder(csShowProductCat::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['get'])
      ->getMock();

    $expected_result = [
      'category' => 'test_term_id',
      'name' => 'test'
    ];

    $response = new ResourceResponse($expected_result);
    $response->addCacheableDependency($expected_result);

    $result = $contact_mock->get(10);
    $this->assertIsObject($result);

  }

  /**
   * Test for post method
   *
   * @return void
   */
  public function testGetWhenNodeIsNotObject() {

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
    $header_bag->method('get')
      ->with('Client-Id')
      ->willReturn('test');

    $request = Request::createFromGlobals();
    $request->headers = $header_bag;
    $request_stack->expects($this->any())
      ->method('getCurrentRequest')
      ->willReturn($request);


    // Mocking node Entity
    $node_storage = $this->getMockBuilder('Drupal\node\NodeStorage')
      ->disableOriginalConstructor()
      ->getMock();

    $node_storage->method('load')
      ->with(10)
      ->willReturn(['test']);

    // Mocking term

    $entity_type_manager = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeManager')
      ->disableOriginalConstructor()
      ->getMock();

    $entity_type_manager->method('getStorage')
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

    $contact_mock = $this->getMockBuilder(csNews::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['get'])
      ->getMock();

    $access_mock = $this->expectException(AccessDeniedHttpException::class);

    $result = $contact_mock->get(10);
    $this->assertEquals($access_mock, $result);

  }

}
