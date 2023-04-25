<?php

namespace Drupal\domain_role_access\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\domain_role_access\Form\DomainRolesForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class DomainRolesController.
 *
 * @package Drupal\domain_role_access\Controller
 */
class DomainRolesController extends ControllerBase {

  /**
   * EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Constructs a DomainRolesController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Edit domain roles.
   *
   * @return array
   *   Edit form page.
   */
  public function edit($domain) {
    if (!$domain = $this->entityTypeManager->getStorage('domain')->load($domain)) {
      throw new NotFoundHttpException();
    }
    $build = [
      'edit_form' => $this->formBuilder()->getForm(DomainRolesForm::class, $domain),
    ];

    return $build;
  }

}
