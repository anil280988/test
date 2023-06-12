<?php

namespace Drupal\contentservice\tests;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\Serializer\SerializerInterface;
use Drupal\contentservice\Service\GenericService;
use Symfony\Component\HttpFoundation\HeaderBag;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\rest\ResourceResponse;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;
use Drupal;

/**
 * Provides unit tests for the csUpdateContactTest Plugin.
 *
 * @coversDefaultClass \Drupal\contentservice\Plugin\rest\resource\csUserCreate
 * @group contentservice
 */
class csUserCreateTest extends UnitTestCase
{
  /**
   * Test for post method
   *
   * @return void
   */
  public function testPostCreateNewUser() {

    $data= [
      'representation' => [
          'name' => 'name',
          'email' => 'test@t.com',
          'emailVerified' => 1,
          'enabled' => 1,
          'firstName' => 'test first name',
          'lastName' => 'test last name',
          'totp' => 'tot p',
          'username' => 'testusername',
          'realmId' => 'testrealId',
          'attributes' => [
            'clientId' => [
              'client-id'
            ]
          ],
          'groups' => [
            'userportal'
          ]
      ],
      'time' => '1685423311',
      'resourcePath' => 'users/test-path',
      'realmId' => 'test-realm_id'
    ];

    $values = [
      'name' => $data['representation']['email'],
      'mail'=> $data['representation']['email'],
      'field_createdtimestamp'=> date('Y-m-d\TH:i:s', substr($data['time'], 0, -3)),
      'field_emailverified'=> $data['representation']['emailVerified'],
      'field_enabled'=> $data['representation']['enabled'],
      'field_first_name'=> $data['representation']['firstName'],
      'field_keyclockuserid'=>  str_replace('users/','',$data['resourcePath']),
      'field_last_name'=> $data['representation']['lastName'],
      'field_realmid'=> $data['realmId'],
      'field_totp'=> $data['representation']['totp'],
      'field_username'=>  $data['realmId'].'---'.$data['representation']['username'],
      'field_clientid'=> $data['representation']['attributes']['clientId'][0],
      'status'=> 1,
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
      ->with('News', 'update')
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
      ->willReturn([0 => ['target_id' => 'test_domain_id1']]);

    // Mocking node Entity
    $user_mock = $this->getMockBuilder('Drupal\user\Entity\User')
      ->disableOriginalConstructor()
      ->getMock();

    $user_mock->method('id')
      ->willReturn('test_user_id');

    $user_storage = $this->getMockBuilder('Drupal\user\UserStorage')
      ->disableOriginalConstructor()
      ->getMock();

    $user_storage->method('loadByProperties')
      ->with(['mail' => $data['representation']['email']])
      ->willReturn('');

    $user_storage->method('create')
      ->with($values)
      ->willReturn($user_mock);

    $entity_repository = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeRepository')
      ->disableOriginalConstructor()
      ->getMock();

    $entity_repository->method('getEntityTypeFromClass')
      ->with('Drupal\user\Entity\User')
      ->willReturn('user');

    $entity_type_manager = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeManager')
      ->disableOriginalConstructor()
      ->getMock();

    $entity_type_manager->expects($this->any())
      ->method('getStorage')
      ->with('user')
      ->willReturn($user_storage);

    $time_mock = $this->getMockBuilder(Drupal\Component\Datetime\Time::class)
      ->disableOriginalConstructor()
      ->getMock();

    $container = new ContainerBuilder();
    Drupal::setContainer($container);
    $container->set('contentservice.GenericService', $service_mock);
    $container->set('request_stack', $request_stack);
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('entity_type.repository', $entity_repository);
    $container->set('datetime.time', $time_mock);

    $user_created_mock = $this->getMockBuilder(Drupal\contentservice\Plugin\rest\resource\csUserCreate::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['post'])
      ->getMock();

    $expected_result['response'] = ['status' => 'success', 'message' => 'User is Updated Successfully', 'userID' => 'test_user_id',];

    $response = new ResourceResponse($expected_result);
    $response->addCacheableDependency($expected_result);

    $result = $user_created_mock->post($data);
    $this->assertIsObject($result);

  }
  /**
   * Test for post method
   *
   * @return void
   */
  public function testPostCreateNewUserWhenStatusIsFalse() {

    $data= [
      'representation' => [
        'name' => 'name',
        'email' => 'test@t.com',
        'emailVerified' => FALSE,
        'enabled' => 1,
        'firstName' => 'test first name',
        'lastName' => 'test last name',
        'totp' => 'tot p',
        'username' => 'testusername',
        'realmId' => 'testrealId',
        'attributes' => [
          'clientId' => [
            'client-id'
          ]
        ],
        'groups' => [
          'userportal'
        ],
        'createdTimestamp' => '1685423311',
      ],
      'time' => '1685423311',
      'resourcePath' => 'users/test-path',
      'realmId' => 'test-realm_id'
    ];

    $values = [
      'name' => $data['representation']['email'],
      'mail'=> $data['representation']['email'],
      'field_createdtimestamp'=> date('Y-m-d\TH:i:s', substr($data['time'], 0, -3)),
      'field_emailverified'=> $data['representation']['emailVerified'],
      'field_enabled'=> $data['representation']['enabled'],
      'field_first_name'=> $data['representation']['firstName'],
      'field_keyclockuserid'=>  str_replace('users/','',$data['resourcePath']),
      'field_last_name'=> $data['representation']['lastName'],
      'field_realmid'=> $data['realmId'],
      'field_totp'=> $data['representation']['totp'],
      'field_username'=>  $data['realmId'].'---'.$data['representation']['username'],
      'field_clientid'=> $data['representation']['attributes']['clientId'][0],
      'status'=> 0,
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
      ->with('News', 'update')
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
      ->willReturn([0 => ['target_id' => 'test_domain_id1']]);

    // Mocking node Entity
    $user_mock = $this->getMockBuilder('Drupal\user\Entity\User')
      ->disableOriginalConstructor()
      ->getMock();

    $user_mock->method('id')
      ->willReturn('test_user_id');

    $user_storage = $this->getMockBuilder('Drupal\user\UserStorage')
      ->disableOriginalConstructor()
      ->getMock();

    $user_storage->method('loadByProperties')
      ->with(['mail' => $data['representation']['email']])
      ->willReturn('');

    $user_storage->method('create')
      ->with($values)
      ->willReturn($user_mock);

    $entity_repository = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeRepository')
      ->disableOriginalConstructor()
      ->getMock();

    $entity_repository->method('getEntityTypeFromClass')
      ->with('Drupal\user\Entity\User')
      ->willReturn('user');

    $entity_type_manager = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeManager')
      ->disableOriginalConstructor()
      ->getMock();

    $entity_type_manager->expects($this->any())
      ->method('getStorage')
      ->with('user')
      ->willReturn($user_storage);

    $time_mock = $this->getMockBuilder(Drupal\Component\Datetime\Time::class)
      ->disableOriginalConstructor()
      ->getMock();

    $container = new ContainerBuilder();
    Drupal::setContainer($container);
    $container->set('contentservice.GenericService', $service_mock);
    $container->set('request_stack', $request_stack);
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('entity_type.repository', $entity_repository);
    $container->set('datetime.time', $time_mock);

    $user_created_mock = $this->getMockBuilder(Drupal\contentservice\Plugin\rest\resource\csUserCreate::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['post'])
      ->getMock();

    $expected_result['data'] = ['status' => 'success', 'message' => 'User Sucessfully Created', 'userID' => 'test_user_id',];

    $response = new ResourceResponse($expected_result);
    $response->addCacheableDependency($expected_result);

    $result = $user_created_mock->post($data);
    $this->assertIsObject($result);

  }

  /**
   * Test for post method
   *
   * @return void
   */
  public function testPostUpdateUser() {

    $data= [
      'representation' => [
        'name' => 'name',
        'email' => 'test@t.com',
        'emailVerified' => 1,
        'enabled' => 1,
        'firstName' => 'test first name',
        'lastName' => 'test last name',
        'totp' => 'tot p',
        'username' => 'testusername',
        'realmId' => 'testrealId',
        'attributes' => [
          'clientId' => [
            'client-id'
          ]
        ],
        'groups' => [
          'userportal'
        ],
        'createdTimestamp' => '1685423311',
      ],
      'time' => '1685423311',
      'resourcePath' => 'users/test-path',
      'realmId' => 'test-realm_id'
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
      ->with('News', 'update')
      ->willReturn('Allow');

    // mocking requrest
    // Mock field

    $field_domain_mock = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $field_domain_mock->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['target_id' => 'test_domain_id1']]);

    // Mocking node Entity
    $user_mock = $this->getMockBuilder('Drupal\user\Entity\User')
      ->disableOriginalConstructor()
      ->getMock();

    $user_mock->method('id')
      ->willReturn('test_user_id');

    $user_storage = $this->getMockBuilder('Drupal\user\UserStorage')
      ->disableOriginalConstructor()
      ->getMock();

    $user_storage->method('loadByProperties')
      ->with(['mail' => $data['representation']['email']])
      ->willReturn([$user_mock]);

    $user_storage->method('save')
      ->willReturnSelf();

    $entity_repository = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeRepository')
      ->disableOriginalConstructor()
      ->getMock();

    $entity_repository->method('getEntityTypeFromClass')
      ->with('Drupal\user\Entity\User')
      ->willReturn('user');

    $entity_type_manager = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeManager')
      ->disableOriginalConstructor()
      ->getMock();

    $entity_type_manager->expects($this->any())
      ->method('getStorage')
      ->with('user')
      ->willReturn($user_storage);

    $time_mock = $this->getMockBuilder(Drupal\Component\Datetime\Time::class)
      ->disableOriginalConstructor()
      ->getMock();

    $container = new ContainerBuilder();
    Drupal::setContainer($container);
    $container->set('contentservice.GenericService', $service_mock);
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('entity_type.repository', $entity_repository);
    $container->set('datetime.time', $time_mock);

    $user_created_mock = $this->getMockBuilder(Drupal\contentservice\Plugin\rest\resource\csUserCreate::class)
      ->disableOriginalConstructor()
      ->setMethodsExcept(['post'])
      ->getMock();

    $expected_result['response'] = [
      'status' => 'success',
      'message' => 'User Sucessfully Updated',
      'userID' => 'test_user_id',
    ];

    $response = new ResourceResponse($expected_result);
    $response->addCacheableDependency($expected_result);

    $result = $user_created_mock->post($data);
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

    $contact_mock = $this->getMockBuilder(Drupal\contentservice\Plugin\rest\resource\csUpdateContact::class)
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
