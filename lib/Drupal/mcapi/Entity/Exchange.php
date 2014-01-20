<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\Exchange.
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Field\FieldDefinition;
//use Drupal\mcapi\CurrencyInterface;
//use Drupal\Core\Entity\Annotation\EntityType;
//use Drupal\Core\Annotation\Translation;
//use Drupal\mcapi\Plugin\Field\FieldType\Worth;

/**
 * Defines the Exchange entity.
 *
 * @EntityType(
 *   id = "mcapi_exchange",
 *   label = @Translation("Exchange"),
 *   module = "mcapi",
 *   controllers = {
 *     "storage" = "Drupal\Core\Entity\FieldableDatabaseStorageController",
 *     "view_builder" = "Drupal\mcapi\ExchangeViewBuilder",
 *     "access" = "Drupal\Core\Entity\EntityAccessController",
 *     "form" = {
 *       "add" = "Drupal\mcapi\Form\ExchangeForm",
 *       "edit" = "Drupal\mcapi\Form\ExchangeForm",
 *       "delete" = "Drupal\mcapi\Form\ExchangeDeleteConfirm",
 *     },
 *     "list" = "Drupal\mcapi\ExchangeListController",
 *   },
 *   admin_permission = "configure mcapi",
 *   fieldable = TRUE,
 *   translatable = FALSE,
 *   base_table = "mcapi_exchanges",
 *   route_base_path = "admin/accounting/exchanges",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "weight" = "weight",
 *     "status" = "status"
 *   },
 *   links = {
 *     "canonical" = "mcapi.exchange.view",
 *     "edit-form" = "mcapi.exchange.edit",
 *     "admin-form" = "mcapi.admin_exchange_list"
 *   }
 * )
 */
class Exchange extends ContentEntityBase {

  public static function preCreate(EntityStorageControllerInterface $storage_controller, array &$values) {
    //set defaults - does this apply on content entities?

  }

  /*
   * possible functions...
   * getMembers()
   * getTransactions()
   * AddMember
   */
  function members() {
    return 100;
  }
  function transactions() {
    return 100;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions($entity_type) {

    $properties['id'] = FieldDefinition::create('integer')
    ->setLabel('Exchange ID')
    ->setDescription('the unique exchange ID')
    ->setReadOnly(TRUE)
    ->setRequired(TRUE);
    $properties['uuid'] = FieldDefinition::create('uuid')
    ->setLabel('UUID')
    ->setDescription('The transaction UUID.')
    ->setReadOnly(TRUE)
    ->setRequired(TRUE);
    $properties['name'] = FieldDefinition::create('string')
    ->setLabel('Full name')
    ->setDescription('The full name of the exchange')
    ->setPropertyConstraints('value', array('Length' => array('max' => 64)))
    ->setRequired(TRUE);
    $properties['uid'] = FieldDefinition::create('entity_reference')
    ->setLabel('Adminstrator')
    ->setDescription('The one user responsible for administration')
    ->setSettings(array('target_type' => 'user'))
    ->setRequired(TRUE);
    $properties['langcode'] = FieldDefinition::create('language')
    ->setLabel(t('Language code'))
    ->setDescription(t('The first language of the exchange'));
    //plus don't forget there is an entityreference field api field and instance called exchange_currencies

    return $properties;
  }

}

