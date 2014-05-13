<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\Exchange.
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Drupal\mcapi\ExchangeInterface;

define('EXCHANGE_VISIBILITY_PRIVATE', 0);
define('EXCHANGE_VISIBILITY_RESTRICTED', 1);
define('EXCHANGE_VISIBILITY_TRANSPARENT', 2);

/**
 * Defines the Exchange entity.
 *
 * @ContentEntityType(
 *   id = "mcapi_exchange",
 *   label = @Translation("Exchange"),
 *   controllers = {
 *     "storage" = "Drupal\mcapi\Storage\ExchangeStorage",
 *     "view_builder" = "Drupal\mcapi\ViewBuilder\ExchangeViewBuilder",
 *     "access" = "Drupal\mcapi\Access\ExchangeAccessController",
 *     "form" = {
 *       "add" = "Drupal\mcapi\Form\ExchangeForm",
 *       "edit" = "Drupal\mcapi\Form\ExchangeForm",
 *       "delete" = "Drupal\mcapi\Form\ExchangeDeleteConfirm",
 *       "activate" = "Drupal\mcapi\Form\ExchangeOpenConfirm",
 *       "deactivate" = "Drupal\mcapi\Form\ExchangeCloseConfirm",
 *     },
 *     "list_builder" = "Drupal\mcapi\ListBuilder\ExchangeListBuilder",
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
class Exchange extends ContentEntityBase implements EntityOwnerInterface, ExchangeInterface{

  /**
   * {@inheritdoc}
   */
  function members() {
    //@todo
    //entity_load_by_properties seems expensive and I don't know how to make it work
    //return entity_load_multiple_by_properties('user', array('field_exchanges' => $this->id()));

    //this more direct solution belongs really in the storage controller
    //@todo how the hell does countQuery work?
    return count(db_select("user__exchanges", 'e')
      ->fields('e', array('entity_id'))
      ->condition('exchanges_target_id', $this->id())
      ->execute()->fetchCol());
  }

  /**
   * {@inheritdoc}
   */
  function transactions($period = 0) {
    //TODO is it worth making a new more efficient function in the storage controller for this?
    $conditions = array('exchange' => $this->get('id')->value, 'since' => strtotime($period));
    $serials = \Drupal::EntityManager()->getStorage('mcapi_transaction')->filter($conditions);
    return count(array_unique($serials));
  }



  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage_controller, $update = TRUE) {
    //ensure the manager of the exchange is actually a member.
    $exchange_manager = user_load($this->get('uid')->value);
    //TODO I can't see how to ensure the owner is actually a member
    //should be done in form validation of course
    //tricky entity_reference handling
    //throw an error if the owner is not already a member
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields['id'] = FieldDefinition::create('integer')
      ->setLabel(t('Exchange ID'))
      ->setDescription(t('The unique exchange ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = FieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The exchange UUID.'))
      ->setReadOnly(TRUE);

    $fields['name'] = FieldDefinition::create('string')
      ->setLabel(t('Full name'))
      ->setDescription(t('The full name of the exchange.'))
      ->setRequired(TRUE)
      ->setSettings(array('default_value' => '', 'max_length' => 64))
      ->setDisplayOptions(
        'view',
        array('label' => 'hidden', 'type' => 'string', 'weight' => -5)
      );

    $fields['uid'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('Manager of the exchange'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE);

    $fields['active'] = FieldDefinition::create('boolean')
      ->setLabel(t('Active'))
      ->setDescription(t('TRUE if the exchange is current and working.'))
      ->setSettings(array('default_value' => TRUE));

    $fields['open'] = FieldDefinition::create('boolean')
      ->setLabel(t('Open'))
      ->setDescription(t('TRUE if the exchange can trade with other exchanges'))
      ->setSettings(array('default_value' => TRUE));

    $fields['visibility'] = FieldDefinition::create('integer')
      ->setLabel(t('Visibility'))
      ->setDescription(t('Visibility of impersonal data in the exchange'))
      ->setRequired(TRUE)
      ->setSetting('default_value', EXCHANGE_VISIBILITY_RESTRICTED);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function member(ContententityInterface $entity) {
    $entity_type = $entity->getEntityTypeId();
    if ($entity_type == 'mcapi_wallet') {
      if ($entity->owner) {
        $entity = $entity->owner;
      }
      else return FALSE;
    }
    module_load_include('inc', 'mcapi');
    $fieldname = get_exchange_entity_fieldnames($entity_type);
    //@todo I can't work out how to do this with the entity_reference property api
    //so just using the database for now.
    return db_select($entity_type .'__'.$fieldname, 'f')
      ->fields('f', array('entity_id'))
      ->condition($fieldname.'_target_id', $this->id())
      ->condition('entity_id', $entity->id())
      ->execute()->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function isManager(AccountInterface $account = NULL) {
    if (is_null($account)) {
      $account = \Drupal::currentUser();
    }
    return $account->id() == $this->get('uid')->value;
  }

  /**
   * {@inheritdoc}
   */
  function intertrading_wallet() {
    $wallets = entity_load_multiple_by_properties('mcapi_wallet', array('name' => '_intertrade', 'pid' => $this->id()));
    if (wallets)return current($wallets);
    throw new Exception('no _intertrade wallet for Exchange '.$this->id());
  }


  //utility function not in the interface
  public function visibility_options($val = NULL) {
    $options = array(
      EXCHANGE_VISIBILITY_PRIVATE => t('Private except to members'),
      EXCHANGE_VISIBILITY_RESTRICTED => t('Restricted to members of this site'),
      EXCHANGE_VISIBILITY_TRANSPARENT => t('Transparent to the public')
    );
    if ($val) return $options[$val];
    return $options;
  }


  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  //in parent, configEntityBase, $rel is set to edit-form by default - why would that be?
  //Is is assumed that every entity has an edit-form link? Any case this overrides it
  public function urlInfo($rel = 'canonical') {
    return parent::urlInfo($rel);
  }

}

