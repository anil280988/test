<?php

namespace Drupal\Tests\getjwtonlogin\Unit\EventSubscriber;

use Drupal\Component\Serialization\Json;
use Drupal\getjwtonlogin\EventSubscriber\JwtLoginSubscriber;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests JwtLoginSubscriber.
 *
 * @coversDefaultClass \Drupal\getjwtonlogin\EventSubscriber\JwtLoginSubscriber
 *
 * @group getjwtonlogin
 */
class JwtLoginSubscriberTest extends UnitTestCase {

  /**
   * The JWT Auth Service.
   *
   * @var \Drupal\jwt\Authentication\Provider\JwtAuth
   */
  protected $jwtAuth;

  /**
   * The HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->jwtAuth = $this->getMockBuilder('\Drupal\jwt\Authentication\Provider\JwtAuth')
      ->disableOriginalConstructor()
      ->getMock();
    $this->jwtAuth->expects($this->any())
      ->method('generateToken')
      ->willReturn($this->getRandomGenerator()->string());

    $this->httpKernel = $this->createMock('Symfony\Component\HttpKernel\HttpKernelInterface');
  }

  /**
   * Tests onHttpLoginResponse method on not user login request.
   */
  public function testOnHttpLoginResponseNotLoginRequest() {
    // Create the response event subscriber.
    $subscriber = new JwtLoginSubscriber($this->jwtAuth);

    // Create the response event.
    $request = new Request();
    $request->attributes->set('_route', 'user.logout.http');
    $response = new Response('{}', 200);
    $event = new FilterResponseEvent($this->httpKernel, $request, HttpKernelInterface::MASTER_REQUEST, $response);

    // Call the event handler.
    $subscriber->onHttpLoginResponse($event);

    // Check the response content has no access token.
    $response_content = Json::decode($event->getResponse()->getContent());
    $this->assertArrayNotHasKey('access_token', $response_content);
  }

  /**
   * Tests onHttpLoginResponse method on error response.
   */
  public function testOnHttpLoginResponseErrorResponse() {
    // Create the response event subscriber.
    $subscriber = new JwtLoginSubscriber($this->jwtAuth);

    // Create the response event.
    $request = new Request();
    $request->attributes->set('_route', 'user.login.http');
    $response = new Response('{}', 400);
    $event = new FilterResponseEvent($this->httpKernel, $request, HttpKernelInterface::MASTER_REQUEST, $response);

    // Call the event handler.
    $subscriber->onHttpLoginResponse($event);

    // Check the response content has no access token.
    $response_content = Json::decode($event->getResponse()->getContent());
    $this->assertArrayNotHasKey('access_token', $response_content);
  }

  /**
   * Tests onHttpLoginResponse method on success.
   */
  public function testOnHttpLoginResponseSuccess() {
    // Create the response event subscriber.
    $subscriber = new JwtLoginSubscriber($this->jwtAuth);

    // Create the response event.
    $request = new Request();
    $request->attributes->set('_route', 'user.login.http');
    $response = new Response(JSON::encode(['current_user' => ['uid' => '1']]), 200);
    $event = new FilterResponseEvent($this->httpKernel, $request, HttpKernelInterface::MASTER_REQUEST, $response);

    // Call the event handler.
    $subscriber->onHttpLoginResponse($event);

    // Check the response content for an access token.
    $response_content = Json::decode($event->getResponse()->getContent());
    $this->assertNotEmpty($response_content['access_token']);
  }

}
