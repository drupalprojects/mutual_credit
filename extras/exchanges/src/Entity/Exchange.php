<?php

/**
 * @file
 * Contains \Drupal\mcapi_exchanges\Entity\Exchange.
 * @todo make this work with OG and OG roles
 */

namespace Drupal\mcapi_exchanges\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\mcapi\Entity\Wallet;
use Drupal\mcapi_exchanges\ExchangeInterface;

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
 *     "storage" = "Drupal\mcapi_exchanges\ExchangeStorage",
 *     "view_builder" = "Drupal\mcapi_exchanges\ExchangeViewBuilder",
 *     "access" = "Drupal\mcapi_exchanges\ExchangeAccessControlHandler",
 *     "list_builder" = "Drupal\mcapi_exchanges\ExchangeListBuilder"
 *     "form" = {
 *       "add" = "Drupal\mcapi_exchanges\Form\ExchangeWizard",
 *       "edit" = "Drupal\mcapi_exchanges\Form\ExchangeForm",
 *       "delete" = "Drupal\mcapi_exchanges\Form\ExchangeDeleteConfirm",
 *       "masspay" = "Drupal\mcapi_exchanges\Form\MassPay",
 *       "enable" = "Drupal\mcapi_exchanges\Form\ExchangeEnableConfirm",
 *       "disable" = "Drupal\mcapi_exchanges\Form\ExchangeDisableConfirm",
 *     },
 *     "views_data" = "Drupal\mcapi_exchanges\ExchangeViewsData"
 *   },
 *   admin_permission = "configure mcapi",
 *   translatable = FALSE,
 *   base_table = "mcapi_exchange",
 *   field_ui_base_route = "mcapi.admin_exchange_list",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "weight" = "weight",
 *     "status" = "status"
 *   }
 *   links = {
 *     "canonical" = "entity.mcapi_exchange.canonical",
 *     "edit-form" = "mcapi.exchange.edit",
 *     "delete-form" = "mcapi.exchange.delete_confirm",
 *     "enable" = "mcapi.exchange.enable_confirm",
 *     "disable" = "mcapi.exchange.disable_confirm"
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
    //@todo add the manager user to the exchange if it is not a member
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
      ->setDescription(t('The official contact address'));

    $fields['created'] = BaseFieldDefinition::create('created')
    ->setLabel(t('Created'))
    ->setDescription(t('The time that the transaction was created.'))
    ->setTranslatable(TRUE)
    ->setDisplayOptions('view', array(
      'label' => 'hidden',
      'type' => 'timestamp',
    ))
    ->setDisplayConfigurable('form', TRUE);;

    return $fields;
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
  public function transactions($children = FALSE) {
    $xids = db_select('og_membership', 'm')
      ->fields('m', array('etid'))
      ->condition('entity_type', 'mcapi_transaction')
      ->condition('group_type', 'mcapi_exchange')
      ->condition('gid', $this->id())
      ->execute()->fetchCol();

    if ($xids && !$children) {
      $filtered = $this->entityManager()
        ->getStorage('mcapi_transaction')
        ->filter(array('xid' => $xids));
      $xids = array_unique($filtered);
    }
    return count($xids);
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

    /**
   * {@inheritdoc}
   */
  function deactivatable() {
    static $active_exchange_ids = [];
    if (!$active_exchange_ids) {
      //get the names of all the open exchanges
      foreach (Self::loadMultiple() as $entity) {
        if ($entity->get('status')->value) {
          $active_exchange_ids[] = $entity->id();
        }
      }
    }
    if ($this->get('status')->value && count($active_exchange_ids) > 1)return TRUE;
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function ___isMember($exchange, ContententityInterface $entity) {
    if ($this->multiple) {
      return _og_is_member(
        'mcapi_exchange',
        $this->id(),
        $entity->getEntityType(),
        $entity,
        array(OG_STATE_ACTIVE)
      );

      //@todo remove all this
      if ($entity->getEntityTypeId() == 'mcapi_wallet') {
        $entity = $entity->getOwner();
      }
      $id = $entity->id();
      $members = $this->{EXCHANGE_OG_REF}->referencedEntities();

      foreach ($members as $account) {
        if ($account->id() == $id) return TRUE;
      }
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function users() {
    return db_select('og_membership', 'm')
      ->fields('m', array('etid'))
      ->condition('entity_type', 'user')
      ->condition('group_type', 'mcapi_exchange')
      ->condition('gid', $this->id())
      ->execute()->fetchCol();
  }


}



