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
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;
use Drupal\user\Entity\User;
use Drupal\mcapi\Entity\Wallet;

define('EXCHANGE_VISIBILITY_PRIVATE', 0);
define('EXCHANGE_VISIBILITY_RESTRICTED', 1);
define('EXCHANGE_VISIBILITY_TRANSPARENT', 2);

/**
 * Defines the Exchange entity.
 *
 * @ContentEntityType(
 *   id = "mcapi_exchange",
 *   label = @Translation("Exchange"),
 *   base_table = "mcapi_exchange",
 *   controllers = {
 *     "storage" = "Drupal\mcapi\Storage\ExchangeStorage",
 *   },
 *   admin_permission = "configure mcapi",
 *   fieldable = TRUE,
 *   translatable = FALSE,
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
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    //TODO add the manager user to the exchange if it is not a member
    //$exchange_manager = User::load($this->get('uid')->value);

    //create a new wallet for new exchanges
    if (!$update) {
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

    $fields['mail'] = FieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setDescription(t('The official contact address'))
      ->setRequired(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function is_member(ContententityInterface $entity) {
    if ($entity_type == 'mcapi_wallet') {
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
  public function isManager(AccountInterface $account = NULL) {
    if (is_null($account)) {
      $account = \Drupal::currentUser();
    }
    return $account->id() == $this->uid->value;
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
  public function _____urlInfo($rel = 'canonical') {
    return parent::urlInfo($rel);
  }

  /**
   * {@inheritdoc}
   */
  public function hello(ContentEntityInterface $entity) {
    $moduleHandler = \Drupal::moduleHandler();
    drupal_set_message('User !name joined exchange !exchange,');
    $moduleHandler->invokeAll('exchange_join', array($entity, $left));
  }

  /**
   * {@inheritdoc}
   */
  public function goodbye(ContentEntityInterface $entity) {
    $moduleHandler = \Drupal::moduleHandler();
    drupal_set_message('User !name left exchange !exchange.');
    $moduleHandler->invokeAll('exchange_leave', array($entity, $left));
  }

  /**
   * {@inheritdoc}
   */
  public function transactions($inclusive = TRUE) {
    //get all the wallets in this exchange
    $wids = mcapi_wallets_in_exchanges(array($this->id()));
    $conditions = array();
    //get all the transactions involving these wallets
    if ($inclusive) {
      $conditions['including'] = $wids;
      $conditions['states'] = \Drupal::config('mcapi.misc')->get('counted');
    }
    else {
      $conditions['involving'] = $wids;
    }
    $serials = $this->entityManager()->getStorage('mcapi_transaction')->filter($conditions);
    if ($inclusive) $serials = array_unique($serials);
    return count($serials);
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
   * @todo is it worth caching this? cache would be cleared whenever fieldInfo changes
   */
  static function getEntityFieldnames() {
    static $types = array();
    if (empty($types)) {
      //get all the instances of these fields
      $field_defs = entity_load_multiple_by_properties('field_storage_config', array('type' => 'entity_reference'));
      unset($field_defs['mcapi_transaction.exchange']);
      foreach ($field_defs as $field) {
        if ($field->settings['target_type'] == 'mcapi_exchange') {
          $types[$field->entity_type] = $field->name;
        }
      }
    }
    return $types;
  }

}


