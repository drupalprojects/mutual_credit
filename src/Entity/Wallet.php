<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\Wallet.
 * @todo make a walletInterface
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\mcapi\Entity\WalletInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the wallet entity.
 * NB This does not implement the ownerInterface because
 * wallets can belong to ANY content entities.
 * NB the canonical link is actually a 'views' page
 *
 * @ContentEntityType(
 *   id = "mcapi_wallet",
 *   label = @Translation("Wallet"),
 *   module = "mcapi",
 *   controllers = {
 *     "view_builder" = "Drupal\mcapi\ViewBuilder\WalletViewBuilder",
 *     "storage" = "Drupal\mcapi\Storage\WalletStorage",
 *     "access" = "Drupal\mcapi\Access\WalletAccessController",
 *     "form" = {
 *       "edit" = "Drupal\mcapi\Form\WalletForm",
 *     },
 *   },
 *   admin_permission = "configure mcapi",
 *   base_table = "mcapi_wallets",
 *   entity_keys = {
 *     "id" = "wid",
 *     "uuid" = "uuid"
 *   },
 *   fieldable = TRUE,
 *   translatable = FALSE,
 *   links = {
 *     "canonical" = "mcapi.wallet_view",
 *     "admin-form" = "mcapi.admin_wallets"
 *   }
 * )
 */
class Wallet extends ContentEntityBase implements WalletInterface{

  private $owner;
  private $stats = array();
  //access settings
  private $access;



  /**
   * {@inheritdoc}
   * @todo update this in line with https://drupal.org/node/2221879
   */
  public function uri() {
    return array(
      'path'=> 'wallet/'.$this->id()
    );
  }

  /**
   * get the wallet's owner, as determined by the wallet's own properties.
   */
  public function getOwner() {
    if (!isset($this->owner)) {
      $this->owner = entity_load($this->entity_type->value, $this->pid->value);
      if (!$this->owner) {
        drupal_set_message('Wallet '.$this->id() .'had no owner.');
        $this->owner = entity_load('mcapi_exchange', 1);
      }
    }
    //if for some reason there isn't an owner, return exchange 1 so as not to break things
    return $this->owner;
  }

  /**
   * {@inheritdoc}
   */
  public function user_id() {
    $owner = $this->getOwner();
    if ($owner instanceof \Drupal\user\UserInterface) return $owner->id();
    //because all wallet owners, whatever entity type, implement OwnerInterface
    else return $owner->getOwnerId();
  }

  /**
   * {@inheritdoc}
   */
  public function label($langcode = NULL, $full = TRUE) {
    //normally you would display the $full name to all users and the wallet->name only to the owner.
    $output = '';
    //we need to decide whether / when to display the owner and when the wallet name

    $name = $this->name->value;
    if ($full || !$name) {
      $output = $this->getOwner()->label() .": ";
    }
    if ($name == '_intertrade') {
      $output .= t('Import/Export');
    }
    elseif ($name) {
      $output .= $name;
    }

    return $output.' #'.$this->wid->value;
  }



  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    if (!array_key_exists('pid', $values) && array_key_exists('entity_type', $values)) {
      throw new Exception("new wallets must have an entity_type and a parent entity_id (pid)");
    }
    //put the default values for the access here
    $access_settings = \Drupal::config('mcapi.wallets')->getRawData();
    foreach (Wallet::ops() as $op) {
      //$values[$op] = key(array_filter($access_settings[$op]));
    }
  }

  /**
   * {@inheritdoc}
   * make the access settings a bit readier to use
   */
  public static function postLoad(EntityStorageInterface $storage, array &$entities) {
    foreach (Wallet::ops() as $op) {
      foreach ($entities as $entity) {
        $entity->access[$op] = $entity->{$op}->value;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    //check the name length
    if (strlen($this->name->value) > 64) {
      $this->name->value = substr($this->name->value, 0, 64);
      drupal_set_message(t('Wallet name was truncated to 64 characters: !name', array('!name' => $this->name->value)), 'warning');
    }
    parent::save();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields['wid'] = FieldDefinition::create('integer')
      ->setLabel(t('Wallet ID'))
      ->setDescription(t('The unique wallet ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = FieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The wallet UUID.'))
      ->setReadOnly(TRUE);

    $fields['name'] = FieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t("The owner's name for this wallet"))
      ->addConstraint('max_length', 64)
      ->setDisplayOptions(
          'view',
          array('label' => 'hidden', 'type' => 'string', 'weight' => -5))
      ->setSetting('default_value', array(0 => ''));//if we leave the default to be NULL it is difficult to filter with mysql

    $fields['entity_type'] = FieldDefinition::create('string')
      ->setLabel(t('Owner entity type'))
      ->setDescription(t('The timezone of this user.'))
      ->setSetting('max_length', 32)
      ->setRequired(TRUE);

    $fields['pid'] = FieldDefinition::create('integer')
      ->setLabel(t('Owner entity ID'));

    $access_settings = \Drupal::config('mcapi.wallets');
    $fields['details'] = FieldDefinition::create('integer')
      ->setLabel(t('Details'))
      ->setDescription(t('Access code for viewing transaction details'))
      ->setSetting('default_value', array(0 => key(array_filter($access_settings->get('details')))));

    $fields['summary'] = FieldDefinition::create('integer')
      ->setLabel(t('Summary'))
      ->setDescription(t('Access code for viewing wallet summary'))
      ->setSetting('default_value', array(0 => key(array_filter($access_settings->get('summary')))));

    $fields['payin'] = FieldDefinition::create('integer')
      ->setLabel(t('Pay in'))
      ->setDescription(t('Access code for paying in'))
      ->setSetting('default_value', array(0 => key(array_filter($access_settings->get('payin')))));

    $fields['payout'] = FieldDefinition::create('integer')
      ->setLabel(t('Pay out'))
      ->setDescription(t('Access code for paying out'))
      ->setSetting('default_value', array(0 => key(array_filter($access_settings->get('payout')))));

    $fields['orphaned'] = FieldDefinition::create('boolean')
      ->setLabel(t('Orphaned'));

    return $fields;
  }

  /**
   * get the exchanges which this wallet can be used in.
   * @return array
   *   exchange entities, keyed by id
   */
  function in_exchanges() {
    $owner = $this->getOwner();
    if ($owner->getEntityTypeId() == 'mcapi_exchange') {
      return array($owner->id() => $owner);
    }
    return referenced_exchanges($owner);
  }
  /**
   * get a list of all the currencies used and available to the wallet.
   */
  function currencies_all() {
    //that means unused currencies should appear last
    return $this->currencies_used() + $this->currencies_available();
  }

  /**
   * get a list of the currencies held in the wallet
   */
  function currencies_used() {
    if (!$this->currencies_used) {
      $this->currencies_used = array();
      foreach (entity_load_multiple('mcapi_currency', array_keys($this->getSummaries())) as $currency) {
        $this->currencies_used[$currency->id()] = $currency;
      }
    }

    return $this->currencies_used;
  }

  /**
   * get a list of all the currencies in this wallet's scope
   */
  function currencies_available() {
    if (!isset($this->currencies_available)) {
      foreach (exchange_currencies($this->in_exchanges()) as $currency) {
        $this->currencies_available[$currency->id()] = $currency;
      }
    }
    return $this->currencies_available;
  }

  /**
   * {@inheritDoc}
   */
  function getSummaries() {
    if (!$this->stats) {
      $this->stats = \Drupal::Entitymanager()->getStorage('mcapi_transaction')->summaryData($this->id());
      foreach ($this->currencies_available() as $curr_id => $currency) {
        if (!array_key_exists($curr_id, $this->stats)) {
          $this->stats[$curr_id] = array(
            'balance' => 0,
          	'trades' => 0,
            'volume' => 0,
            'gross_in' => 0,
            'gross_out' => 0,
            'partners' => 0
          );
        }
      }
    }
    return $this->stats;
  }

  /**
   * (non-PHPdoc)
   * @see \Drupal\mcapi\Entity\WalletInterface::getStats()
   */
  function getStats($curr_id) {
    $summaries = $this->getSummaries();
    if (array_key_exists($curr_id, $summaries)) return $summaries[$curr_id];
    //else return NULL
  }

  /**
   * (non-PHPdoc)
   * @see \Drupal\mcapi\Entity\WalletInterface::getStat()
   */
  function getStat($curr_id, $stat) {
    $stats = getStats($curr_id);
    if (array_key_exists($curr_id, $stats)) return $stats[$curr_id];
    //else return NULL
  }


  /**
   * {@inheritdoc}
   */
  //in parent, configEntityBase, $rel is set to edit-form by default - why would that be?
  //Is is assumed that every entity has an edit-form link? Any case this overrides it
  public function urlInfo($rel = 'canonical') {
    return parent::urlInfo($rel);
  }

  /**
   *
   * @return array
   *   all transactions between the times given
   */
  public function history($from = 0, $to = 0) {
    $conditions = array(
    	'involving' => $this->id()
    );
    if ($from) {
      $conditions['from'] = $from;
    }
    if ($to) {
      $conditions['to'] = $to;
    }
    return \Drupal::entitymanager()->getStorage('mcapi_transaction')->filter($conditions);
  }

  /**
   * (non-PHPdoc)
   * @see \Drupal\mcapi\Entity\WalletInterface::orphan()
   */
  public function orphan(ExchangeInterface $exchange = NULL) {
    $conditions = array('involving' => $this->id());
    if (\Drupal\mcapi\Storage\TransactionStorage::filter($conditions)) {
      $new_name = t(
        "Formerly !name's wallet: !label",
        array('!name' => $this->label(), '!label' => $this->label(NULL, FALSE))
      );
      $this->set('name', $new_name);
      $this->set('entity_type', 'mcapi_exchange');
      $this->set('pid', $exchange->id());
      //TODO make the number of wallets an exchange can own to be unlimited.
      drupal_set_message(t(
        "!name's wallets are now owned by exchange !exchange",
        array('!name' => $this->label(), '!exchange' => l($exchange->label(), $exchange->url()))
      ));
      $this->save();
    }
    else {
      $this->delete();
    }
  }


  /**
   * update the wallet exchange index table
   */
  public function postSave(EntityStorageInterface $storage_controller, $update = TRUE) {
    $storage_controller->updateIndex($this);
  }

  public static function postDelete(EntityStorageInterface $storage_controller, array $entities) {
    $storage_controller->dropIndex(array_keys($entities));
  }

  //TODO put permissions and ops in the WalletInterface
  static function permissions() {
    return array(
      //TODO only wallets owned by user entities can have this option
      WALLET_ACCESS_OWNER => t('Just the owner'),
      WALLET_ACCESS_EXCHANGE => t('Members in the same exchange(s)'),//todo: which exchanges?
      WALLET_ACCESS_AUTH => t('Any logged in users'),
      WALLET_ACCESS_ANY => t('Anyone on the internet'),
      WALLET_ACCESS_USERS => t('Named users...')
    );
  }
  public static function ops() {
    return array('details', 'summary', 'payin', 'payout');
  }
}
