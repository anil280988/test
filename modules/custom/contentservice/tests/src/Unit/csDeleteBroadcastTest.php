<?php

namespace Drupal\contentservice\tests;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Consolidation\OutputFormatters\Formatters\SerializeFormatter;
use Drupal\contentservice\Plugin\rest\resource\csDeleteBroadcast;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\Serializer\SerializerInterface;
use Drupal\contentservice\Service\GenericService;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ServerBag;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Config\ConfigFactory;
use Drupal\rest\ResourceResponse;
use Drupal\Core\Config\Config;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;
use Drupal;

class csDeleteBroadcastTest extends UnitTestCase
{

  /**
   * Test for post method
   *
   * @return void
   */
  public function testDelete()
  {

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
      ->with('Broadcast', 'delete')
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

    $request_stack->expects($this->at(0))
      ->method('getCurrentRequest')
      ->willReturn($request);
    // Mock field

    $field_domain_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $field_domain_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['target_id' => 'test_domain_id']]);

    // Mocking node Entity
    $node_mock = $this->getMockBuilder('Drupal\node\Entity\Node')
      ->disableOriginalConstructor()
      ->getMock();

    $node_mock->expects($this->at(0))
      ->method('get')
      ->with('field_domain_access')
      ->willReturn($field_domain_mock);

    $node_storage = $this->getMockBuilder('Drupal\node\NodeStorage')
      ->disableOriginalConstructor()
      ->getMock();

    $node_storage->method('load')
      ->with('test_nid')
      ->willReturn($node_mock);

    $time_mock = $this->getMockBuilder(Drupal\Component\Datetime\Time::class)
      ->disableOriginalConstructor()
      ->getMock();

    $node_storage->method('delete')
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

    $product_mock = $this->getMockBuilder(Drupal\contentservice\Plugin\rest\resource\csDeleteBroadcast::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['delete'])
      ->getMock();

    $result = $product_mock->delete('test_nid');
    $this->assertIsObject($result);
  }

  /**
   * Test case when domain is empty
   *
   * @return void
   */
  public function testDeleteWhenDomainIsEmpty()
  {


    // service
    $service_mock = $this->getMockBuilder(GenericService::class)
      ->disableOriginalConstructor()
      ->getMock();

    $service_mock->method('userDuplicateLoginValidation')
      ->willReturn(1);

	$service_mock->method('UserPermissionCheck')
      ->with('Broadcast', 'delete')
      ->willReturn('Allow');

    $service_mock->method('getDomainIdFromClientId')
      ->with('test_cleint_id')
      ->willReturn('');

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

    $request_stack->expects($this->at(0))
      ->method('getCurrentRequest')
      ->willReturn($request);
    // Mock field

    $container = new ContainerBuilder();
    Drupal::setContainer($container);
    $container->set('contentservice.GenericService', $service_mock);
    $container->set('request_stack', $request_stack);

    $product_mock = $this->getMockBuilder(Drupal\contentservice\Plugin\rest\resource\csDeleteBroadcast::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['delete'])
      ->getMock();

    $result = $product_mock->delete('test_nid');
    $this->assertIsObject($result);
  }


  /**
   * Test case when title is Empty
   *
   * @return void
   */
  public function testDeleteWhenDomainNotMatch()
  {

    // service
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
      ->with('Broadcast', 'delete')
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

    $request_stack->expects($this->at(0))
      ->method('getCurrentRequest')
      ->willReturn($request);

    // Mocking node Entity
    $node_mock = $this->getMockBuilder('Drupal\node\Entity\Node')
      ->disableOriginalConstructor()
      ->getMock();

    $field_domain_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_domain_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['target_id' => 'sample_domain_id']]);

    $node_mock->expects($this->any())
      ->method('get')
      ->with('field_domain_access')
      ->willReturn($field_domain_mock);


    $node_storage = $this->getMockBuilder('Drupal\node\NodeStorage')
      ->disableOriginalConstructor()
      ->getMock();

    $node_storage->method('load')
      ->with('test_nid')
      ->willReturn($node_mock);

    $time_mock = $this->getMockBuilder(Drupal\Component\Datetime\Time::class)
      ->disableOriginalConstructor()
      ->getMock();

    $node_storage->method('delete')
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


    $access_mock = $this->expectException(AccessDeniedHttpException::class);

    $product_mock = $this->getMockBuilder(Drupal\contentservice\Plugin\rest\resource\csDeleteBroadcast::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['delete'])
      ->getMock();

    $result = $product_mock->delete('test_nid');
    $this->assertEquals($access_mock, $result);

  }


  /**
   * Test case when Permission Denied
   *
   * @return void
   */
  public function testDeleteWhenPermissionDenied()
  {

    // service
    $service_mock = $this->getMockBuilder(GenericService::class)
      ->disableOriginalConstructor()
      ->getMock();
    $service_mock->method('userDuplicateLoginValidation')
      ->willReturn(1);

    $service_mock->method('UserPermissionCheck')
      ->with('Broadcast', 'delete')
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

    $product_mock = $this->getMockBuilder(Drupal\contentservice\Plugin\rest\resource\csDeleteBroadcast::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['delete'])
      ->getMock();

    $result = $product_mock->delete('test_nid');
    $this->assertNull($result);
  }

  /**
   * Test case when Permission Denied
   *
   * @return void
   */
  public function testDeleteWhenInvalidLogin()
  {

    // service
    $service_mock = $this->getMockBuilder(GenericService::class)
      ->disableOriginalConstructor()
      ->getMock();

    $service_mock->method('userDuplicateLoginValidation')
      ->willReturn(0);

    $service_mock->method('UserPermissionCheck')
      ->with('Broadcast', 'delete')
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

    $contact_mock = $this->getMockBuilder(Drupal\contentservice\Plugin\rest\resource\csDeleteBroadcast::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['delete'])
      ->getMock();

    $result = $contact_mock->delete('test_nid');
    $this->assertEquals($access_mock, $result);
  }

  /**
   * Test case when type is 3
   *
   * @return void
   */
  public function testDeleteWhenNodeIsEmpty()
  {

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
      ->with('Broadcast', 'delete')
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

    $request_stack->expects($this->at(0))
      ->method('getCurrentRequest')
      ->willReturn($request);

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

    $product_mock = $this->getMockBuilder(Drupal\contentservice\Plugin\rest\resource\csDeleteBroadcast::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['delete'])
      ->getMock();

    $result = $product_mock->delete('test_nid');
    $this->assertIsObject($result);

  }

  /**
   * Test case for Create
   *
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

    $serializer_mock = $this->getMockBuilder(SerializerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $container->set('serializer.formats', $serializer_mock);
    $container->set('logger.factory', $logger);
    $container->set('current_user', $accountproxy);

    Drupal::setContainer($container);

    $contact_mock = $this->getMockBuilder(Drupal\contentservice\Plugin\rest\resource\csDeleteBroadcast::class)
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















