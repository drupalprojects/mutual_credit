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
 * Wallets can only belong to content entities.
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
  private $stats;

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
   * {@inheritdoc}
   */
  public function id() {
    return $this->get('wid')->value;
  }

  /**
   *
   */
  public function getOwner() {
    return $this->owner;
  }

  /**
   * {@inheritdoc}
   */
  public function label($langcode = NULL, $full = TRUE) {
    $output = '';
    //we need to decide whether / when to display the owner and when the wallet name
    if ($full) {
      if ($this->owner) {
        $output = $this->owner->label();
      }
      else {
        $output = t('System');
      }
      $output .= ": ";
    }

    $val = $this->get('name')->value;
    if ($val == '_intertrade') {
      $output .= t('Import/Export');
    }
    elseif ($val) {
      $output .= $val;
    }
    else $output .= t('Wallet #@num', array('@num' => $this->get('wid')->value));

    return $output;
  }

  /**
   * Whenever a wallet is loaded, prepare the owner entity, and the trading statistics
   *
   * @param WalletStorageInterface $storage_controller
   * @param array $entities
   */
  public static function postLoad(EntityStorageInterface $storage_controller, array &$entities) {
    $transaction_storage = \Drupal::EntityManager()->getStorage('mcapi_transaction');
    foreach ($entities as $wallet) {
      $wallet->loadOwner($wallet->get('entity_type')->value, $wallet->get('pid')->value);
      $wallet->stats = $transaction_storage->summaryData($wallet->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    if (array_key_exists('pid', $values) && array_key_exists('entity_type', $values)) {
      return;
    }
    throw new Exception("new wallets must have an entity_type and a parent entity_id (pid)");
  }
  /**
   * {@inheritdoc}
   */
  public function postCreate(EntityStorageInterface $storage) {
    $this->loadOwner($this->get('entity_type')->value, $this->get('pid')->value);
  }
  public function loadOwner($entity_type, $pid) {
    if ($entity_type) {
      $this->owner = entity_load($entity_type, $pid);
    }
    if (empty($this->owner)){
      echo 'wallet has no owner';
    }
  }

  public function save() {
    //check the name length
    if (property_exists($this, 'name') && strlen($this->name) > 32) {
      $this->name = substr($this->name, 0, 32);
      drupal_set_message(t('Wallet name was truncated to 32 characters: !name', array('!name' => $this->name)), 'warning');
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

    //as I understand, we can only use the entity reference field for a known entity type.
    //so we have to use 2 fields here to refer to the owner entity

    $fields['entity_type'] = FieldDefinition::create('string')
    ->setLabel(t('Owner entity type'))
    ->setDescription(t('The timezone of this user.'))
    ->setSetting('max_length', 32)
    ->setRequired(TRUE);

    $fields['orphaned'] = FieldDefinition::create('boolean')
      ->setLabel(t('Orphaned'));

    $fields['pid'] = FieldDefinition::create('integer')
      ->setLabel(t('Owner entity ID'));

    $fields['name'] = FieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t("The owner's name for this wallet"))
      ->addConstraint('max_length', 64)
      ->setDisplayOptions(
        'view',
        array('label' => 'hidden', 'type' => 'string', 'weight' => -5)
      );

    /*
    $properties['viewers'] = FieldDefinition::create('string')
      ->setLabel('Access controller')
      ->setDescription("The class which controls view access to this wallet")
      ->setRequired(TRUE);
    $properties['payers'] = FieldDefinition::create('string')
      ->setLabel('Access controller')
      ->setDescription("The class which controls payer access to this wallet")
      ->setRequired(TRUE);
    $properties['payees'] = FieldDefinition::create('string')
      ->setLabel('Access controller')
      ->setDescription("The class which controls payee access to this wallet")
      ->setRequired(TRUE);
*/
    //+ a field to store the access control settings may be needed.
    return $fields;
  }

  /**
   * get the exchanges which this wallet can be used in.
   * @return array
   *   exchange entities, keyed by id
   */
  function in_exchanges() {
    if (!$this->owner) return array();
    if ($this->owner->getEntityTypeId() == 'mcapi_exchange') {
      return array($this->owner->id() => $this->owner);
    }
    return referenced_exchanges($this->owner);
  }

  /**
   * get a list of the currencies held in the wallet
   */
  function currencies() {
    if (!$this->currencies) {
      $this->currencies = entity_load_multiple('mcapi_currency', array_keys($this->getStats()));
    }
    return $this->currencies;
  }

  /**
   * get a list of all the currencies in this wallet's scope
   */
  function currencies_available() {
    //echo "<br />wallet ".$this->id()." is in exchanges ".implode(', ', array_keys($this->in_exchanges()));
    //echo " and can use currencies ".implode(', ', array_keys(exchange_currencies($this->in_exchanges())));
    return exchange_currencies($this->in_exchanges());
  }

  /**
   * {@inheritDoc}
   */
  function getStats($curr_id = NULL) {
    if ($curr_id) {
      if (array_key_exists($curr_id, $this->stats)) return $this->stats[$curr_id];
      else return array();
    }
    return $this->stats;
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

  //todo put this in the interface
  public function orphan(ExchangeInterface $exchange = NULL) {
    //Delete the wallet if it has no transaction history
    //otherwise ownership moves to the given exchange
    //if no exchange given, the wallet has no parents.
    $transactions = \Drupal::Entitymanager()
      ->getStorage('mcapi_transaction')
      ->filter(array('involving' => $this->id()));
    if (!$transactions) {
      $this->delete();
      drupal_set_message('Deleted wallet '.$this->id());
    }
    else {
      $new_name = t(
        "Formerly !name's wallet: !label",
        array('!name' => $entity->label(), '!label' => $this->label(NULL, FALSE))
      );
      $this->set('name', $new_name);
      $this->set('entity_type', 'mcapi_exchange');
      $this->set('pid', $exchange->id());
      //TODO make the number of wallets an exchange can own to be unlimited.
      drupal_set_message(t(
        "!name's wallets are now owned by exchange !exchange",
        array('!name' => $entity->label(), '!exchange' => l($exchange->label(), $exchange->url()))
      ));
      $wallet->save();
    }
  }


  /**
   * update the wallet exchange index table
   */
  public function postSave(EntityStorageInterface $storage_controller, $update = TRUE) {
    $storage_controller->updateIndex($this);
  }

  public static function postDelete(EntityStorageInterface $storage_controller, array $entities) {
    $storage_controller->dropIndex($entities);
  }

}
