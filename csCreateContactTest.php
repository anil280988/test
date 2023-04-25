<?php

namespace Drupal\contentservice\tests;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Consolidation\OutputFormatters\Formatters\SerializeFormatter;
use Drupal\contentservice\Plugin\rest\resource\csCreateContact;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\contentservice\Service\GenericService;
use Symfony\Component\HttpFoundation\HeaderBag;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Config\ConfigFactory;
use Drupal\rest\ResourceResponse;
use Drupal\Core\Config\Config;
use Psr\Log\LoggerInterface;
use Drupal;

/**
 * Provides unit tests for the csCreateContactTest Plugin.
 *
 * @coversDefaultClass \Drupal\contentservice\src\Plugin\rest\resource\csCreateContactTest
 * @group contentservice
 */
class csCreateContactTest extends Drupal\Tests\UnitTestCase {

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

    $contact_mock = $this->getMockBuilder(csCreateContact::class)
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

  

  

}
