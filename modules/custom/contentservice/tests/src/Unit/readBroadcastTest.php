<?php

namespace Drupal\contentservice\tests;

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

class readBroadcastTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->pluginId = 'showproductUpdate';
    $this->pluginDefinition['title'] = 'show all updates';
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
    $showprodupdates_object = new readBroadcast(
      $this->configuration,
      $this->pluginId,
      $this->pluginDefinition,
      ['serializer.formats' => 'serializer.formats'],
      $logger,
      $current_user
    );
    // Assert that the object was created successfully and that the dependency was set.
    $this->assertInstanceOf(readBroadcast::class, $showprodupdates_object);

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
      ->with('broadcast')
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
    $createnewsc_object = readBroadcast::create($container, $this->configuration, $this->pluginId, $this->pluginDefinition);
    $this->assertTrue(TRUE);
  }

  public function testPost() {

    $data['nid'] = 'test_nid';

    // service
    $service_mock = $this->getMockBuilder(GenericService::class)
      ->disableOriginalConstructor()
      ->getMock();
    $service_mock->method('userDuplicateLoginValidation')
      ->willReturn(1);

    // Database query mock
    $db_connection = $this->getMockBuilder(Connection::class)
      ->disableOriginalConstructor()
      ->getMock();

    $insert_mock = $this->getMockBuilder('\Drupal\Core\Database\Query\Insert')
      ->disableOriginalConstructor()
      ->getMock();

    $insert_mock->expects($this->at(0))->method('fields')
      ->with(['nid', 'uid'])
      ->willReturnSelf();

    $insert_mock->expects($this->at(1))->method('values')
      ->with(['nid' => 'test_nid', 'uid' => 'test_uid'])
      ->willReturnSelf();

    $insert_mock->expects($this->at(2))
      ->method('execute')
      ->willReturnSelf();

    $db_connection->method('select')
      ->willReturn($insert_mock);

    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
    $container->set('contentservice.GenericService', $service_mock);
    $container->set('database', $db_connection);

    $contact_mock = $this->getMockBuilder(readBroadcast::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['post'])
      ->getMock();

    $expected_result= ['status' => 'success', 'message' => 'Broadcast ID is Read Successfully'];

    $response = new ResourceResponse($expected_result);
    $response->addCacheableDependency($expected_result);

    $result = $contact_mock->post($data);
    $this->assertIsObject($result);

  }

}
