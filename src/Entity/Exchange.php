<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\Exchange.
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;
use Drupal\user\Entity\User;
use Drupal\mcapi\Entity\Wallet;
use Drupal\mcapi\ExchangeInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Cache\CacheBackendInterface;

define('EXCHANGE_VISIBILITY_PRIVATE', 0);
define('EXCHANGE_VISIBILITY_RESTRICTED', 1);
define('EXCHANGE_VISIBILITY_TRANSPARENT', 2);

/**
 * Defines the Exchange entity.
 *
 * @ContentEntityType(
 *   id = "mcapi_exchange",
 *   label = @Translation("Exchange"),
 *   handlers = {
 *     "storage" = "Drupal\mcapi\Storage\ExchangeStorage",
 *   },
 *   admin_permission = "configure mcapi",
 *   translatable = FALSE,
 *   base_table = "mcapi_exchange",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "weight" = "weight",
 *     "status" = "status"
 *   }
 * )
 */
class Exchange extends ContentEntityBase implements EntityOwnerInterface, ExchangeInterface{

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    $values += array(
      //'name' => t("!name's exchange", array('!name' => $account->label())),
      'uid' => \Drupal::currentUser()->id() ? : 1,//drush is user 0
      'status' => TRUE,
      'open' => TRUE,
      'visibility' => TRUE,
    );
  }

  /**
   * Create the _intertrading wallet and ensure the manager user is in the exchange
   * @param EntityStorageInterface $storage
   * @param boolean $update
   *   whether the wallet already existed
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    //TODO add the manager user to the exchange if it is not a member
    //$exchange_manager = User::load($this->get('uid')->value);

    //create a new wallet for new exchanges
    if (!$update) {
      $this->addIntertradingWallet();
    }
  }
  
  public function addIntertradingWallet() {
    $props = array(
      'entity_type' => 'mcapi_exchange',
      'pid' => $this->id(),
      'name' => '_intertrading',
      'details' => WALLET_ACCESS_AUTH,
      'summary' => WALLET_ACCESS_AUTH,
      'payin' => WALLET_ACCESS_AUTH,
      'payout' => WALLET_ACCESS_AUTH
    );
    $wallet = Wallet::create($props);
    $wallet->save();
  }

  /**
   * {@inheritdoc}
   */
  function users() {
    return count(db_select("user__exchanges", 'e')
      ->fields('e', array('entity_id'))
      ->condition('exchanges_target_id', $this->id())
      ->execute()->fetchCol());
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Exchange ID'))
      ->setDescription(t('The unique exchange ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The exchange UUID.'))
      ->setReadOnly(TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Full name'))
      ->setDescription(t('The full name of the exchange.'))
      ->setRequired(TRUE)
      ->setSettings(array('default_value' => '', 'max_length' => 64))
      ->setDisplayOptions(
        'view',
        array('label' => 'hidden', 'type' => 'string', 'weight' => -5)
      );

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Manager of the exchange'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active'))
      ->setDescription(t('TRUE if the exchange is current and working.'))
      ->setSettings(array('default_value' => TRUE));

    $fields['open'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Open'))
      ->setDescription(t('TRUE if the exchange can trade with other exchanges'))
      ->setSettings(array('default_value' => TRUE));

    $fields['visibility'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Visibility'))
      ->setDescription(t('Visibility of impersonal data in the exchange'))
      ->setRequired(TRUE)
      ->setSetting('default_value', EXCHANGE_VISIBILITY_RESTRICTED);

    $fields['mail'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setDescription(t('The official contact address'))
      ->setRequired(TRUE);

    
    //TODO in beta2, this field is required by views. Delete if pos
    $fields['langcode'] = BaseFieldDefinition::create('language')
    ->setLabel(t('Language code'))
    ->setDescription(t('language code.'))
    ->setSettings(array('default_value' => 'und'));
    
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function is_member(ContententityInterface $entity) {
    if ($entity->getEntityTypeId() == 'mcapi_wallet') {
      $entity = $entity->getOwner();
    }
    $id = $entity->id();
    $fieldnames = $this->getEntityFieldnames();
    $fieldname = $fieldnames[$entity->getEntityTypeId()];
    $members = $this->{$fieldname}->referencedEntities();

    foreach ($members as $account) {
      if ($account->id() == $id) return TRUE;
    }
    return FALSE;
  }


  /**
   * {@inheritdoc}
   */
  function intertrading_wallet() {
    $props = array('name' => '_intertrading', 'pid' => $this->id());
    $wallets = $this->EntityManager()
      ->getStorage('mcapi_wallet')
      ->loadByProperties($props);
    return reset($wallets);
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
  public function hello(ContentEntityInterface $entity) {
    $moduleHandler = \Drupal::moduleHandler();
    drupal_set_message('User !name joined exchange !exchange,');
    $moduleHandler->invokeAll('exchange_join', array($entity));//TODO
  }

  /**
   * {@inheritdoc}
   */
  public function goodbye(ContentEntityInterface $entity) {
    $moduleHandler = \Drupal::moduleHandler();
    drupal_set_message('User !name left exchange !exchange.');
    $moduleHandler->invokeAll('exchange_leave', array($entity));//TODO
  }

  /**
   * {@inheritdoc}
   */
  public function transactions() {
    //get all the wallets in this exchange
    $wids = $this->entityManager()->getStorage('mcapi_wallet')->inExchanges(array($this->id()));
    //get all the transactions involving these and only these wallets
    if ($wids) {
      $conditions = array('involving'=> $wids);
      $serials = $this->entityManager()->getStorage('mcapi_transaction')->filter($conditions);
      //serials are keyed by xid and there may be duplicates
      return count(array_unique($serials));
    }
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  static function referenced_exchanges(ContentEntityInterface $entity = NULL, $enabled = TRUE, $open = FALSE) {
    $exchanges = array();
    if (is_null($entity)) {
      $entity = User::load(\Drupal::currentUser()->id());
    }
    $entity_type = $entity->getEntityTypeId();
    if ($entity_type == 'mcapi_exchange') {
      //an exchange references itself only
      $exchanges[$entity->id()] = $entity;
    }
    else{
      $fieldnames = Exchange::getEntityFieldnames();
      if ($fieldname = @$fieldnames[$entity_type]) {
        foreach($entity->{$fieldname}->referencedEntities() as $entity) {
          //exclude disabled exchanges
          if (($enabled && !$entity->status->value) || ($open && !$entity->open->value)) {
            continue;
          }
          $exchanges[$entity->id()] = $entity;
        }
      }
    }
    return $exchanges;
  }


  /**
   * {@inheritdoc}
   */
  static function getEntityFieldnames() {
    if ($cache = \Drupal::cache()->get('exchange_references')) {
      $types = $cache->data;
    }
    else{  //get all the instances of these entity_reference fields
      $field_defs = \Drupal::EntityManager()
        ->getStorage('field_storage_config')
        ->loadByProperties(array('type' => 'entity_reference'));
      unset($field_defs['mcapi_transaction.exchange']);//because we don't need it
      //now find only the once which refer to exchanges
      foreach ($field_defs as $field) {
        if ($field->settings['target_type'] == 'mcapi_exchange') {
          $types[$field->entity_type] = $field->field_name;
        }
      }
      \Drupal::cache()->set(
        'exchange_references',
        $types,
        CacheBackendInterface::CACHE_PERMANENT,
        array()//TODO cache tags
      );
    }
    return $types;
  }


  /**
   * {@inheritdoc}
   */
  function deletable() {
    if ($this->get('status')->value) {
      $this->reason = t('Exchange must be disabled');
      return FALSE;
    }
    if (count($this->intertrading_wallet()->history())) {
      $this->reason = t('Exchange intertrading wallet has transactions');
      return FALSE;
    }
    //if the exchange has wallets, even orphaned wallets, it can't be deleted.
    $conditions = array('exchanges' => array($this->id()));
    $wallet_ids = $this->EntityManager()->getStorage('mcapi_wallet')->filter($conditions);
    if (count($wallet_ids) > 1){
      $this->reason = t('The exchange still owns wallets: @nums', array('@nums' => implode(', ', $wallet_ids)));
      return FALSE;
    }
    return TRUE;
  }

}


