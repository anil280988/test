<?php

namespace Drupal\contentservice\tests;

use Drupal\contentservice\Plugin\rest\resource\csUnsetUsers;
use Drupal\contentservice\Plugin\rest\resource\readBroadcast;
use Drupal\contentservice\Service\GenericService;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\ResourceResponse;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class csUnsetUsersTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->pluginId = 'unsetuser';
    $this->pluginDefinition['title'] = 'Unset Users';
    $this->currentUser  = $this->getMockBuilder(AccountProxyInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->serializer_formats = ['serializer.formats' => 'serializer.formats'];
    $this->configuration = [];
  }


  public function testconstruct() {

    // Create a mock object for any dependencies that are required by the constructor.
    $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
    $current_user = $this->getMockBuilder(AccountProxyInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $current_user->method('id')
      ->willReturn('test_uid');

    // Call the constructor with the mocked dependency.
    $showprodupdates_object = new csUnsetUsers(
      $this->configuration,
      $this->pluginId,
      $this->pluginDefinition,
      ['serializer.formats' => 'serializer.formats'],
      $logger,
      $current_user
    );
    // Assert that the object was created successfully and that the dependency was set.
    $this->assertInstanceOf(csUnsetUsers::class, $showprodupdates_object);

  }

  public function testCreate() {
    $container = new ContainerBuilder();
    $logger = $this->getMockBuilder(LoggerInterface::class)
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

    $accountproxy->method('id')
      ->willReturn('test_uid');

    //Set container parameters.
    $container->set('current_user', $accountproxy);
    $container->set('logger.factory', $logger_factory);
    $container->setParameter('serializer.formats', $this->serializer_formats);
    \Drupal::setContainer($container);
     csUnsetUsers::create($container, $this->configuration, $this->pluginId, $this->pluginDefinition);
    $this->assertTrue(TRUE);
  }

  public function testPost() {

    $data['mail'] = 'test_mail';

    $request_stack = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')
      ->getMock();
    $request_mock = $this->getMockBuilder(Request::class)
      ->disableOriginalConstructor()
      ->getMock();
    $request_mock->method('getContent')
      ->willReturn(json_encode($data));

    $request_stack->method('getCurrentRequest')
      ->willReturn($request_mock);

    $user_mock = $this->getMockBuilder(User::class)
      ->disableOriginalConstructor()
      ->getMock();

    $user_storage = $this->getMockBuilder('Drupal\user\UserStorage')
      ->disableOriginalConstructor()
      ->getMock();

    $user_storage->method('loadByProperties')
      ->with(['mail' => 'test_mail'])
      ->willReturn([$user_mock]);

    $user_storage->method('save')
      ->willReturnSelf();

    $entity_type_manager = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeManager')
      ->disableOriginalConstructor()
      ->getMock();

    $entity_type_manager->method('getStorage')
      ->with('user')
      ->willReturn($user_storage);

    $entity_repository = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeRepository')
      ->disableOriginalConstructor()
      ->getMock();
    $entity_repository->method('getEntityTypeFromClass')
      ->with('Drupal\user\Entity\User')
      ->willReturn('user');


    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('entity_type.repository', $entity_repository);
    $container->set('request_stack', $request_stack);

    $contact_mock = $this->getMockBuilder(csUnsetUsers::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['post'])
      ->getMock();

    $expected_result= ['status' => 'success', 'message' => 'User is Delete Successfully', 'userID' => 'test_user_id'];

    $response = new ResourceResponse($expected_result);
    $response->addCacheableDependency($expected_result);

    $result = $contact_mock->post(['mail' => 'test_mail']);
    $this->assertIsObject($result);

  }
  public function testPostWhenNoUserIsObject() {

    $data['mail'] = 'test_mail';

    $request_stack = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')
      ->getMock();
    $request_mock = $this->getMockBuilder(Request::class)
      ->disableOriginalConstructor()
      ->getMock();
    $request_mock->method('getContent')
      ->willReturn(json_encode($data));

    $request_stack->method('getCurrentRequest')
      ->willReturn($request_mock);

    $user_mock = $this->getMockBuilder(User::class)
      ->disableOriginalConstructor()
      ->getMock();

    $user_storage = $this->getMockBuilder('Drupal\user\UserStorage')
      ->disableOriginalConstructor()
      ->getMock();

    $user_storage->method('loadByProperties')
      ->with(['mail' => 'test_mail'])
      ->willReturn([]);

    $entity_type_manager = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeManager')
      ->disableOriginalConstructor()
      ->getMock();

    $entity_type_manager->method('getStorage')
      ->with('user')
      ->willReturn($user_storage);

    $entity_repository = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeRepository')
      ->disableOriginalConstructor()
      ->getMock();
    $entity_repository->method('getEntityTypeFromClass')
      ->with('Drupal\user\Entity\User')
      ->willReturn('user');

    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('entity_type.repository', $entity_repository);
    $container->set('request_stack', $request_stack);

    $contact_mock = $this->getMockBuilder(csUnsetUsers::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['post'])
      ->getMock();

    $expected_result= ['status' => 'success', 'message' => 'User is Delete Successfully', 'userID' => 'test_user_id'];

    $response = new ResourceResponse($expected_result);
    $response->addCacheableDependency($expected_result);

    $result = $contact_mock->post(['mail' => 'test_mail']);
    $this->assertIsObject($result);

  }

}
