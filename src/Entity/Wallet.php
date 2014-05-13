<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\Wallet.
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;

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
   * @param EntityStorageInterface $storage_controller
   * @param array $entities
   */
  public static function postLoad(EntityStorageInterface $storage_controller, array &$entities) {
    $transaction_storage = \Drupal::EntityManager()->getStorage('mcapi_transaction');
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
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {

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

    $fields['pid'] = FieldDefinition::create('integer')
      ->setLabel(t('Owner entity ID'));

    $fields['name'] = FieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t("The owner's name for this wallet"))
      ->setSettings(array('default_value' => '', 'max_length' => 32))
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
  /**
   * {@inheritdoc}
   */
  //in parent, configEntityBase, $rel is set to edit-form by default - why would that be?
  //Is is assumed that every entity has an edit-form link? Any case this overrides it
  public function urlInfo($rel = 'canonical') {
    return parent::urlInfo($rel);
  }
}
