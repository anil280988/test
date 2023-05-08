<?php

namespace Drupal\Tests\contentservice\Unit;

use Drupal;
use Drupal\contentservice\Service\GenericService;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\message\MessengerInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ServerBag;

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
   * Test case: when uid is not numberic
   * @return void
   */
  public function testuserDuplicateLoginValidationWhenUIdIsNumeric() {

    $accountproxy = $this->getMockBuilder(AccountProxyInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $accountproxy->method('id')
      ->willReturn(0);

    $container = new ContainerBuilder();
    Drupal::setContainer($container);
    $container->set('current_user', $accountproxy);

    $service_mock = $this->getMockBuilder(GenericService::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['userDuplicateLoginValidation'])
      ->getMock();

    $result = $service_mock->userDuplicateLoginValidation();
    $this->assertEquals(0, $result);

  }

  /**
   * Test case: when token doesn't match
   * @return void
   */
  public function testuserDuplicateLoginValidationWhenTokenNotMatch() {

    $accountproxy = $this->getMockBuilder(AccountProxyInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $accountproxy->method('id')
      ->willReturn(1);

    $user_mock = $this->getMockBuilder('Drupal\user\Entity\User')
      ->disableOriginalConstructor()
      ->getMock();

    $fieldDescMock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $fieldDescMock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'token1']]);

    $user_mock->expects($this->any())
      ->method('get')
      ->with('field_jwt_new')
      ->willReturn($fieldDescMock);

    $user_storage = $this->getMockBuilder(Drupal\user\UserStorage::class)
      ->disableOriginalConstructor()
      ->getMock();

    $user_storage->method('load')
      ->with(1)
      ->willReturn($user_mock);

    $request_stack = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')
      ->getMock();
    $header_bag = $this->getMockBuilder(HeaderBag::class)
      ->disableOriginalConstructor()
      ->getMock();
    $server_bag = $this->getMockBuilder(ServerBag::class)
      ->disableOriginalConstructor()
      ->getMock();
    $header_bag->method('get')
      ->with('Client-Id')
      ->willReturn('test');

    $server_bag->method('get')
      ->with('REDIRECT_HTTP_AUTHORIZATION')
      ->willReturn('token');

    $request = Request::createFromGlobals();
    $request->headers = $header_bag;
    $request->server = $server_bag;

    $request_stack->expects($this->any())
      ->method('getCurrentRequest')
      ->willReturn($request);

    $entity_repository = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeRepository')
      ->disableOriginalConstructor()
      ->getMock();

    $entity_repository->expects($this->at(0))
      ->method('getEntityTypeFromClass')
      ->with('Drupal\user\Entity\User')
      ->willReturn('User');

    $entity_type_manager = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeManager')
      ->disableOriginalConstructor()
      ->getMock();

    $entity_type_manager->expects($this->at(0))
      ->method('getStorage')
      ->with('User')
      ->willReturn($user_storage);

    $container = new ContainerBuilder();
    Drupal::setContainer($container);
    $container->set('request_stack', $request_stack);
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('entity_type.repository', $entity_repository);
    $container->set('current_user', $accountproxy);

    $service_mock = $this->getMockBuilder(GenericService::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['userDuplicateLoginValidation'])
      ->getMock();

    $result = $service_mock->userDuplicateLoginValidation();
    $this->assertEquals(0, $result);

  }


}
