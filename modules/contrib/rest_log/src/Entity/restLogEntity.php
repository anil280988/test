<?php

namespace Drupal\rest_log\Entity;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the rest log entity.
 *
 * @ingroup rest_log
 *
 * @ContentEntityType(
 *   id = "rest_log",
 *   label = @Translation("Rest log entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\rest_log\Entity\restLogEntityViewsData",
 *   },
 *   base_table = "rest_log",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   }
 * )
 */
class restLogEntity extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['request_method'] = BaseFieldDefinition::create('string')
      ->setLabel('Request method')
      ->setDescription('Request method');

    $fields['request_header'] = BaseFieldDefinition::create('string_long')
      ->setLabel('Request header')
      ->setDescription('Request header');

    $fields['request_uri'] = BaseFieldDefinition::create('string')
      ->setLabel('Request uri')
      ->setDescription('Request uri');

    $fields['request_cookie'] = BaseFieldDefinition::create('string_long')
      ->setLabel('Request cookie')
      ->setDescription('Request cookie');

    $fields['request_payload'] = BaseFieldDefinition::create('string_long')
      ->setLabel('Request payload')
      ->setDescription('Request payload');

    $fields['response_status'] = BaseFieldDefinition::create('string')
      ->setLabel('Response status')
      ->setDescription('Response status');

    $fields['response_body'] = BaseFieldDefinition::create('string_long')
      ->setLabel('Response body')
      ->setDescription('Response body');

    $fields['error_code'] = BaseFieldDefinition::create('string')
      ->setLabel('Error code')
      ->setDescription('Error code');

    $fields['error_message'] = BaseFieldDefinition::create('string')
      ->setLabel('Error message')
      ->setDescription('Error message');

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Order change record entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
