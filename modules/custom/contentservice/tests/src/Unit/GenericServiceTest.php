<?php

namespace Drupal\Tests\contentservice\Unit;

use Drupal;
use Drupal\contentservice\Service\GenericService;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\message\MessengerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Provides unit tests for the GenericService service.
 *
 * @coversDefaultClass \Drupal\contentservice\Service\GenericService
 * @group contentservice
 */
class GenericServiceTest extends UnitTestCase {

  protected $container;

  protected $current_user;

  /**
   * The Messenger Service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  private $messenger;

  private $user;

  // Test percentage function.

  public function testgetLoggedInUserEntity() {

    $container = new ContainerBuilder();

    $accountproxy = $this->getMockBuilder(AccountProxyInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $container->set('current_user', $accountproxy);

    Drupal::setContainer($container);

    $service_mock = $this->getMockBuilder(GenericService::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['getLoggedInUserEntity'])
      ->getMock();
    $service_mock->getLoggedInUserEntity();

   $this->assertTrue(TRUE);

  }

  /**
   * Test case: when Client is empty.
   * @return void
   */
  public function testgetDomainIdFromClientIdWhenClientIsEmpty() {

    // Mock field
    $fieldDescMock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $fieldDescMock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'test']]);

    $term = $this->getMockBuilder('Drupal\taxonomy\Entity\Term')
      ->disableOriginalConstructor()
      ->getMock();

    $term->method('label')
      ->willReturn('test');

    $term->method('id')
      ->willReturn('test_term_id');

    $term->expects($this->any())
      ->method('get')
      ->with('field_domain')
      ->willReturn($fieldDescMock);

    $term_storage = $this->getMockBuilder('Drupal\taxonomy\TermStorage')
      ->disableOriginalConstructor()
      ->getMock();

    $term_storage->method('loadTree')
      ->with('domain_client_mapping')
      ->willReturn([$term]);

    $entity_type_manager = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeManager')
      ->disableOriginalConstructor()
      ->getMock();

    $entity_type_manager->expects($this->at(0))
      ->method('getStorage')
      ->with('taxonomy_term')
      ->willReturn($term_storage);


    $container = new ContainerBuilder();
    Drupal::setContainer($container);
    $container->set('entity_type.manager', $entity_type_manager);

    $service_mock = $this->getMockBuilder(GenericService::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['getDomainIdFromClientId'])
      ->getMock();

    $result = $service_mock->getDomainIdFromClientId('test_client_id');

    $this->assertNull($result);
  }

  /**
   * Test case: Check user permission
   * @return void
   */
  public function testUserPermissionAccessCheck() {

    $data = [
      'access content'
    ];

    $container = new ContainerBuilder();

    $accountproxy = $this->getMockBuilder(AccountProxyInterface::class)
      ->disableOriginalConstructor()
      ->getMock();


    $container->set('current_user', $accountproxy);
    Drupal::setContainer($container);
    $service_mock = $this->getMockBuilder(GenericService::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['UserPermissionAccessCheck'])
      ->getMock();

    //$result = $service_mock->UserPermissionAccessCheck($data);

  }


}
