<?php

namespace Drupal\session_inspector\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\session_inspector\SessionDeletionInterface;
use Drupal\session_inspector\SessionInspectorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a confirmation form to allow users to delete sessions.
 *
 * @package Drupal\session_inspector\Form
 */
class DeleteSessionForm extends ConfirmFormBase {

  /**
   * The user Id.
   *
   * @var int
   */
  protected $uid;

  /**
   * The session ID.
   *
   * @var string
   */
  protected $sessionId;

  /**
   * The SessionInspector service.
   *
   * @var \Drupal\session_inspector\SessionInspectorInterface
   */
  protected $sessionInspector;

  /**
   * The session inspector delete all sessions service.
   *
   * @var \Drupal\session_inspector\SessionDeletionInterface
   */
  protected $deleteAllSessionsService;

  /**
   * Constructs a DeleteSessionForm object.
   *
   * @param \Drupal\session_inspector\SessionInspectorInterface $session_inspector
   *   The SessionInspector service.
   * @param \Drupal\session_inspector\SessionDeletionInterface $deleteAllSessionsService
   *   The session delete interface.
   */
  public function __construct(SessionInspectorInterface $session_inspector, SessionDeletionInterface $deleteAllSessionsService) {
    $this->sessionInspector = $session_inspector;
    $this->deleteAllSessionsService = $deleteAllSessionsService;
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $user = NULL, $sid = NULL) {
    $this->user = $user;
    $this->sessionId = $sid;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('session_inspector'),
      $container->get('session_inspector.session_deletion')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getQuestion() {
    return $this->t('Delete the session?');
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
  public function getFormId() {
    return 'session_inspector_delete_form';
  }

  /**
   * {@inheritDoc}
   */
  public function getDescription() {
    return $this->t('This action cannot be undone.');
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Delete the session.
    $this->deleteAllSessionsService->deleteSession($this->sessionId);

    // Redirect the user back to the session list.
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
