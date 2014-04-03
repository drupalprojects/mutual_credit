<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\Wallet.
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Entity\EntityStorageControllerInterface;

/**
 * Defines the wallet entity.
 * Wallets can only belong to content entities.
 * NB the canonical link is actually a 'views' page
 *
 * @EntityType(
 *   id = "mcapi_wallet",
 *   label = @Translation("Wallet"),
 *   module = "mcapi",
 *   controllers = {
 *     "view_builder" = "Drupal\mcapi\WalletViewBuilder",
 *     "storage" = "Drupal\mcapi\WalletStorageController",
 *     "access" = "Drupal\mcapi\WalletAccessController",
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
 *   route_base_path = "admin/accounting/wallets",
 *   links = {
 *     "canonical" = "mcapi.wallet_view",
 *     "admin-form" = "mcapi.admin_wallets"
 *   }
 * )
 */
class Wallet extends ContentEntityBase {

  private $owner;
  private $stats;

  /**
   * {@inheritdoc}
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
   * return the parent entity if there is one, otherwise return the wallet itself
   */
  public function getOwner() {
    if ($this->owner) return $this->owner;
    else return $this;//wallets owned by the system own themselves
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


  private function getOwnerPath() {
    if ($this->owner) {
      $uri = $this->owner->uri();
    }
    else {
      $uri = $this->uri();
    }
    return $uri['path'];
  }

  /**
   * Whenever a wallet is loaded, prepare the owner entity, and the trading statistics
   *
   * @param EntityStorageControllerInterface $storage_controller
   * @param array $entities
   */
  public static function postLoad(EntityStorageControllerInterface $storage_controller, array $entities) {
    $transaction_storage = \Drupal::EntityManager()->getStorageController('mcapi_transaction');
    foreach ($entities as $wallet) {
      $parent_type = $wallet->get('entity_type')->value;
      //they could all have different parent entity types, so we have to load them separately
      if ($parent_type == '') {
        $wallet->owner = NULL;
      }
      else {
        $wallet->owner = entity_load($parent_type, $wallet->get('pid')->value);
      }
      $wallet->stats = $transaction_storage->summaryData($wallet->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageControllerInterface $storage_controller, array &$values) {

    parent::preCreate($storage_controller, $values);
    $values += array(
      'viewers' => 'inherit',
      'payers' => 'autheticated',
      'payees' => 'current'
    );
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
  public static function baseFieldDefinitions($entity_type) {
    $properties['wid'] = array(
      'type' => 'integer_field',
      'label' => t('Wallet ID'),
      'description' => t('The unique wallet ID'),
      'readonly' => TRUE,
      'required' => TRUE
    );
    $properties['uuid'] = array(
      'label' => t('UUID'),
      'description' => t('The wallet UUID.'),
      'type' => 'uuid_field',
      'read-only' => TRUE,
      'required' => TRUE
    );

    //as I understand, we can only use the entity reference field for a known entity type.
    //so we have to use 2 fields here to refer to the owner entity
    $properties['entity_type'] = array(
      'label' => t('Owner entity type'),
      //'description' => t("The owner entity's type"),
      'type' => 'string_field',
      'settings' => array(
        'default_value' => 'restricted',
      ),
      'property_constraints' => array(
        'value' => array('Length' => array('max' => 16)),
      ),
      'required' => TRUE,
    );
    $properties['pid'] = array(
      'type' => 'integer_field',
      'label' => t('Owner entity ID'),
      //'description' => t('The unique exchange ID'),
      'required' => TRUE
    );
    $properties['name'] = array(
      'label' => t('Name'),
      'description' => t("The owner's name for this wallet"),
      'type' => 'string_field',
      'required' => TRUE,
      'property_constraints' => array(
        'value' => array('Length' => array('max' => 32)),//TODO set the form input to max 32
      ),
      'translatable' => FALSE,
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
    return $properties;
  }

  /**
   * get the exchanges which this wallet can be used in.
   * @return array
   *   exchange entities, keyed by id
   */
  function in_exchanges() {
    if (!$this->owner) return array();
    if ($this->owner->entityType() == 'mcapi_exchange') {
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
   * wallet 1 is special and can access all currencies
   */
  function currencies_available() {
    if ($this->get('wid')->value == 1) {
      return entity_load_multiple('mcapi_currency');
    }
    return exchange_currencies($this->in_exchanges());
  }

  /*
   *
   */
  function getStats($currcode = NULL) {
    if ($currcode) {
      if (array_key_exists($currcode, $this->stats)) return $this->stats[$currcode];
      else return array();
    }
    return $this->stats;
  }
}
