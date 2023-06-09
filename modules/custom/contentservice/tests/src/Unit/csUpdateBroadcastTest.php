<?php

namespace Drupal\contentservice\tests;

use Drupal\contentservice\Plugin\rest\resource\csUpdateBroadcast;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\Serializer\SerializerInterface;
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
   * Test for post method
   *
   * @return void
   */
  public function testPut() {

    $data = [
      'title' => 'title',
      'description' => 'test description',
      'product_type' => 'test product type',
      'category' => '5',
      'type' => 1,
      'group' => 'test group'
    ];

    // service
    $service_mock = $this->getMockBuilder(GenericService::class)
      ->disableOriginalConstructor()
      ->getMock();

    $service_mock->method('userDuplicateLoginValidation')
      ->willReturn(1);

    $service_mock->method('getDomainIdFromClientId')
      ->with('test_cleint_id')
      ->willReturn('test_domain_id');

    $service_mock->method('UserPermissionCheck')
      ->with('Broadcast', 'update')
      ->willReturn('Allow');

    // mocking requrest
    $request_stack = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')
      ->getMock();
    $header_bag = $this->getMockBuilder(HeaderBag::class)
      ->disableOriginalConstructor()
      ->getMock();

    $header_bag->method('get')
      ->with('Client-Id')
      ->willReturn('test_cleint_id');

    $request_mock = $this->getMockBuilder(Request::class)
      ->disableOriginalConstructor()
      ->getMock();

    $service_mock->method('userDuplicateLoginValidation')
      ->willReturn(1);

    $request_mock->method('getContent')
      ->willReturn(json_encode($data));

    $request = Request::createFromGlobals();
    $request->headers = $header_bag;

    $request_stack->expects($this->at(0))
      ->method('getCurrentRequest')
      ->willReturn($request);

    $request_stack->expects($this->at(1))
      ->method('getCurrentRequest')
      ->willReturn($request_mock);

    // Mock field

    // Mocking node Entity
    $node_mock = $this->getMockBuilder('Drupal\node\Entity\Node')
      ->disableOriginalConstructor()
      ->getMock();

    $node_storage = $this->getMockBuilder('Drupal\node\NodeStorage')
      ->disableOriginalConstructor()
      ->getMock();

    $node_storage->method('load')
      ->with('test_nid')
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
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('entity_type.repository', $entity_repository);
    $container->set('datetime.time', $time_mock);

    $product_mock = $this->getMockBuilder(Drupal\contentservice\Plugin\rest\resource\csUpdateBroadcast::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['put'])
      ->getMock();

    $expected_result['response'] = [
      'status' => 'success',
      'message' => 'Product Changelog is Created Successfully',
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

    $result = $product_mock->put('test_nid');
    $this->assertIsObject($result);

  }

  /**
   * Test case when domain is empty
   *
   * @return void
   */
  public function testPutWhenDomainIsEmpty() {

    $data = [
      'title' => 'title',
      'description' => 'test description',
      'product_type' => 'test product type',
      'category' => '5',
      'type' => 1,
    ];

    // service
    $service_mock = $this->getMockBuilder(GenericService::class)
      ->disableOriginalConstructor()
      ->getMock();

    $service_mock->method('userDuplicateLoginValidation')
      ->willReturn(1);

    $service_mock->method('getDomainIdFromClientId')
      ->with('test_cleint_id')
      ->willReturn('');

    $service_mock->method('UserPermissionCheck')
      ->with('Broadcast', 'update')
      ->willReturn('Allow');

    // mocking requrest
    $request_stack = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')
      ->getMock();
    $header_bag = $this->getMockBuilder(HeaderBag::class)
      ->disableOriginalConstructor()
      ->getMock();

    $header_bag->method('get')
      ->with('Client-Id')
      ->willReturn('test_cleint_id');

    $request_mock = $this->getMockBuilder(Request::class)
      ->disableOriginalConstructor()
      ->getMock();

    $request_mock->method('getContent')
      ->willReturn(json_encode($data));

    $request = Request::createFromGlobals();
    $request->headers = $header_bag;

    $request_stack->expects($this->at(0))
      ->method('getCurrentRequest')
      ->willReturn($request);

    $request_stack->expects($this->at(1))
      ->method('getCurrentRequest')
      ->willReturn($request_mock);

    // Mock field

    $container = new ContainerBuilder();
    Drupal::setContainer($container);
    $container->set('contentservice.GenericService', $service_mock);
    $container->set('request_stack', $request_stack);

    $product_mock = $this->getMockBuilder(Drupal\contentservice\Plugin\rest\resource\csUpdateBroadcast::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['put'])
      ->getMock();

    $expected_result['response'] = [
      'status' => 'success',
      'message' => 'Product Changelog is Created Successfully',
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

    $result = $product_mock->put('test_nid');
    $this->assertIsObject($result);
  }

  /**
   * Test case when title is Empty
   *
   * @return void
   */
  public function testPutWhenTitleIsEmpty() {

    $data = [
      'title' => '',
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
      ->willReturn(1);

    $service_mock->method('UserPermissionCheck')
      ->with('Broadcast', 'update')
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

    $request_mock = $this->getMockBuilder(Request::class)
      ->disableOriginalConstructor()
      ->getMock();

    $request_mock->method('getContent')
      ->willReturn(json_encode($data));

    $request = Request::createFromGlobals();
    $request->headers = $header_bag;
    $request_stack->expects($this->at(0))
      ->method('getCurrentRequest')
      ->willReturn($request);

    $request_stack->expects($this->at(1))
      ->method('getCurrentRequest')
      ->willReturn($request_mock);

    $access_mock = $this->expectException(AccessDeniedHttpException::class);

    $container = new ContainerBuilder();
    Drupal::setContainer($container);
    $container->set('contentservice.GenericService', $service_mock);
    $container->set('request_stack', $request_stack);

    $product_mock = $this->getMockBuilder(Drupal\contentservice\Plugin\rest\resource\csUpdateBroadcast::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['put'])
      ->getMock();

    $result = $product_mock->put('test_nid');
    $this->assertEquals($access_mock, $result);
  }

  /**
   * Test case when Permission Denied
   *
   * @return void
   */
  public function testPutWhenPermissionDenied()
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
      ->willReturn(1);

    $service_mock->method('UserPermissionCheck')
      ->with('Broadcast', 'update')
      ->willReturn('Disallow');

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
    $request_stack->expects($this->at(0))
      ->method('getCurrentRequest')
      ->willReturn($request);

    $container = new ContainerBuilder();
    Drupal::setContainer($container);
    $container->set('contentservice.GenericService', $service_mock);
    $container->set('request_stack', $request_stack);

    $access_mock = $this->expectException(AccessDeniedHttpException::class);

    $product_mock = $this->getMockBuilder(Drupal\contentservice\Plugin\rest\resource\csUpdateBroadcast::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['put'])
      ->getMock();

    $result = $product_mock->put('test_nid');
    $this->assertEquals($access_mock, $result);
  }

  /**
   * Test case when Permission Denied
   *
   * @return void
   */
  public function testPutWhenInvalidLogin() {

    // service
    $service_mock = $this->getMockBuilder(GenericService::class)
      ->disableOriginalConstructor()
      ->getMock();

    $service_mock->method('userDuplicateLoginValidation')
      ->willReturn(0);

    $service_mock->method('UserPermissionCheck')
      ->with('Broadcast', 'update')
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
    Drupal::setContainer($container);
    $container->set('contentservice.GenericService', $service_mock);
    $container->set('request_stack', $request_stack);

    $contact_mock = $this->getMockBuilder(Drupal\contentservice\Plugin\rest\resource\csUpdateBroadcast::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['put'])
      ->getMock();

    $result = $contact_mock->put('test_nid');
    $this->assertEquals($access_mock, $result);
  }

  /**
   * Test case when type is 3
   *
   * @return void
   */
  public function testPutWhenNodeIsEmpty()
  {
    $data = [
      'title' => 'title',
      'description' => 'test description',
      'product_type' => 'test product type',
      'category' => '5',
      'type' => 1,
      'group' => 'test group'
    ];

    // service
    $service_mock = $this->getMockBuilder(GenericService::class)
      ->disableOriginalConstructor()
      ->getMock();

    $service_mock->method('userDuplicateLoginValidation')
      ->willReturn(1);

    $service_mock->method('getDomainIdFromClientId')
      ->with('test_cleint_id')
      ->willReturn('test_domain_id');

    $service_mock->method('UserPermissionCheck')
      ->with('Broadcast', 'update')
      ->willReturn('Allow');

    // mocking requrest
    $request_stack = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')
      ->getMock();
    $header_bag = $this->getMockBuilder(HeaderBag::class)
      ->disableOriginalConstructor()
      ->getMock();

    $header_bag->method('get')
      ->with('Client-Id')
      ->willReturn('test_cleint_id');

    $request = Request::createFromGlobals();
    $request->headers = $header_bag;
    $request_mock = $this->getMockBuilder(Request::class)
      ->disableOriginalConstructor()
      ->getMock();

    $request_mock->method('getContent')
      ->willReturn(json_encode($data));

    $request = Request::createFromGlobals();
    $request->headers = $header_bag;

    $request_stack->expects($this->at(0))
      ->method('getCurrentRequest')
      ->willReturn($request);

    $request_stack->expects($this->at(1))
      ->method('getCurrentRequest')
      ->willReturn($request_mock);

    // Mock field


    // Mocking node Entity

    $node_storage = $this->getMockBuilder('Drupal\node\NodeStorage')
      ->disableOriginalConstructor()
      ->getMock();

    $node_storage->method('load')
      ->with('test_nid')
      ->willReturn('');

    $time_mock = $this->getMockBuilder(Drupal\Component\Datetime\Time::class)
      ->disableOriginalConstructor()
      ->getMock();

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
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('entity_type.repository', $entity_repository);
    $container->set('datetime.time', $time_mock);

    $product_mock = $this->getMockBuilder(Drupal\contentservice\Plugin\rest\resource\csUpdateBroadcast::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['put'])
      ->getMock();

    $result = $product_mock->put('test_nid');
    $this->assertIsObject($result);

  }

  /**
   * Test case for Create
   *
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

    $serializer_mock = $this->getMockBuilder(SerializerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $container->set('serializer.formats', $serializer_mock);
    $container->set('logger.factory', $logger);
    $container->set('current_user', $accountproxy);

    Drupal::setContainer($container);

    $contact_mock = $this->getMockBuilder(Drupal\contentservice\Plugin\rest\resource\csUpdateBroadcast::class)
      ->setConstructorArgs([
        [],
        'test_plugin',
        'plugin_defination',
        ['xml', 'json', 'hal_json'],
        $logger,
        $accountproxy,
      ])
      ->setMethodsExcept(['create'])
      ->getMock();

    $contact_mock->create($container, [], 'test_plugin', 'plugin_defination');
    $this->assertTrue(TRUE);
  }

}
