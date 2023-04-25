<?php

namespace Drupal\session_inspector\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\session_inspector\SessionDeletionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The class to delete all active sessions form.
 *
 * @package Drupal\session_inspector\Form
 */
class DeleteAllSessionsForm extends ConfirmFormBase {

  /**
   * The User entity.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * The session inspector delete all sessions service.
   *
   * @var \Drupal\session_inspector\SessionDeletionInterface
   */
  protected $deleteAllSessionsService;

  /**
   * Constructs a DeleteAllSessionsForm object.
   *
   * @param \Drupal\session_inspector\SessionDeletionInterface $deleteAllSessionsService
   *   The session inspector delete all sessions service.
   */
  public function __construct(SessionDeletionInterface $deleteAllSessionsService) {
    $this->deleteAllSessionsService = $deleteAllSessionsService;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $user = NULL) {
    $this->user = $user;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('session_inspector.session_deletion')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'session_inspector_delete_confirm_form';
  }

  /**
   * {@inheritDoc}
   */
  public function getDescription() {
    return $this->t('This action will delete all other active sessions and cannot be undone. You will still be logged in with the current session once complete.');
  }

  /**
   * {@inheritDoc}
   */
  public function getQuestion() {
    return $this->t('Delete all other sessions?');
  }

  /**
   * {@inheritDoc}
   */
  public function getCancelUrl() {
    return new Url('session_inspector.manage', ['user' => $this->user->id()]);
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->deleteAllSessionsService->deleteAllSessions($this->user);
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
