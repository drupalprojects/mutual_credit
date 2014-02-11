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
  public function label($langcode = NULL) {
    $display_format = \Drupal::config('mcapi.wallets')->get('display_format');
    $replacements = array('!o' => '', '!w'=> '');
    if (is_numeric(strpos($display_format, '!o'))) {
      $replacements['!o'] =  $this->getOwnerLabel();
    }
    if (is_numeric(strpos($display_format, '!w'))) {
      $replacements['!w'] = $this->get('name')->value;
    }
    return strtr($display_format, $replacements);
  }

  /*
   * get the wallet name, linked to its transaction view, if permissions allow
   * @param boolean $linked
   *   TRUE if a link is required
   * @return string
   *   the name or linked name of the wallet
   */
  function linkedname($linked = TRUE) {
    $display_format = \Drupal::config('mcapi.wallets')->get('display_format');

    $replacements = array('!o' => '', '!w'=> '');
    if (is_numeric(strpos($display_format, '!o'))) {
      $label = $this->getOwnerLabel();
      $replacements['!o'] = ($linked && $this->getOwner()->access('view')) ?
        l($label, $this->getOwnerPath()) :
        $label;
    }

    if (is_numeric(strpos($display_format, '!w'))) {
      $label = $this->get('name')->value;
      if ($linked && $this->access('view')) {
        $uri = $this->uri();
        $replacements['!w'] = l($label, $uri['path']);
      }
      else {
        $replacements['!w'] = ($label);
      }
    }
    return strtr($display_format, $replacements);

  }

  private function getOwnerLabel() {
    if ($this->owner) {
      return $this->owner->label();
    }
    else {
      return t('System');
    }
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
  public static function postLoad(EntityStorageControllerInterface $storage_controller, array &$entities) {
    $transaction_storage = \Drupal::EntityManager()->getStorageController('mcapi_transaction');
    foreach ($entities as $wallet) {
      $parent_type = $wallet->get('entity_type')->value;
      //they could all have different parent entity types, so we have to load them separately
      if ($parent_type == '') {
        $wallet->owner = NULL;// new \Drupal\mcapi\Entity\Bank;
      }
      else {
        //@todo remove this array syntax
        $wallet->owner = entity_load('user', $wallet->get('pid')->value);
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

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions($entity_type) {
    $properties['wid'] = FieldDefinition::create('integer')
      ->setLabel('Wallet ID')
      ->setDescription('the unique wallet ID')
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);
    $properties['uuid'] = FieldDefinition::create('uuid')
      ->setLabel('UUID')
      ->setDescription('The wallet UUID.')
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);
    $properties['entity_type'] = FieldDefinition::create('string')
      ->setLabel('Parent entity type')
      ->setDescription("The parent entity's type")
      ->setRequired(TRUE);
    //as I understand, we can only use the entity reference field for a known entity type.
    $properties['pid'] = FieldDefinition::create('integer')
      ->setLabel('Parent entity ID')
      ->setRequired(TRUE);
    $properties['name'] = FieldDefinition::create('string')
      ->setLabel('Name')
      ->setDescription("The owner's name for this wallet")
      ->setRequired(TRUE);
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
   */
  function currencies_available() {
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
