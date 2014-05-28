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
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    $account = \Drupal::currentUser();
    $values += array(
      //'name' => t("!name's exchange", array('!name' => $account->getlabel())),
      'uid' => $account->id(),
      'status' => TRUE,
      'open' => TRUE,
      'visibility' => TRUE,
      'currencies' => key(entity_load_multiple('mcapi_currency'))
    );
  }

  /**
   * check that the exchange has no wallets or it can't be deleted
   * that means
   */
  public static function preDelete(EntityStorageInterface $storage_controller, array $entities) {
    return;
    foreach ($entities as $exchange) {
      if (!$storage_controller->deletable($exchange)) {
        throw new \Exception(
          t('Exchange @label is not deletable: @reason', array('@label' => $exchange->label(), '@reason' => $exchange->reason ))
        );
      }
    }
  }

  /**
   * Create the _intertrading wallet
   */

  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    //add the manager user to the exchange if it is not a member
    $exchange_manager = user_load($this->get('uid')->value);
    debug(
      $exchange_manager->exchanges->getValue(),
      'the exchange manager, ', $exchange_manager->label() .' is in these exchanges'
    );

    if ($update)return;
    $wallet = entity_create('mcapi_wallet', array('entity_type' => 'mcapi_exchange', 'pid' => $this->id()));
    $wallet->name->setValue('_intertrading');
    $wallet->save();
  }

  /**
   * {@inheritdoc}
   */
  function members() {
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

    $fields['status'] = FieldDefinition::create('boolean')
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
  public function is_member(ContententityInterface $entity) {
    $entity_type = $entity->getEntityTypeId();
    if ($entity_type == 'mcapi_wallet') {
      $entity = $entity->getOwner();
    }

    $fieldnames = get_exchange_entity_fieldnames();
    $id = $entity->id();
    echo 'Exchange::is_member'; print_r($this->{$fieldname}->getValue(FALSE));die();
    foreach ($this->{$fieldnames[$entity_type]}->getValue(FALSE) as $item) {
      if ($item['target_id'] == $id) return TRUE;
    }
    return FALSE;
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
    return current(entity_load_multiple_by_properties(
      'mcapi_wallet',
      array('name' => '_intertrading', 'pid' => $this->id())
    ));
  }


  /**
   * get one or all of the visibililty types with friendly names
   * @param integer $constant
   * @return mixed
   *   an array of visibility type names, keyed by integer constants or just one name
   */
  public function visibility_options($constant = NULL) {
    $options = array(
      EXCHANGE_VISIBILITY_PRIVATE => t('Private except to members'),
      EXCHANGE_VISIBILITY_RESTRICTED => t('Restricted to members of this site'),
      EXCHANGE_VISIBILITY_TRANSPARENT => t('Transparent to the public')
    );
    if (is_null($constant))return $options;
    return $options[$constant];
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

