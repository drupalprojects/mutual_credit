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
 * NB the canonical link is actually a 'views' page
 *
 * @EntityType(
 *   id = "mcapi_wallet",
 *   label = @Translation("Wallet"),
 *   module = "mcapi",
 *   controllers = {
 *     "storage" = "Drupal\Core\Entity\FieldableDatabaseStorageController",
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
 *   fieldable = FALSE,
 *   translatable = FALSE,
 *   route_base_path = "admin/accounting/wallets",
 *   links = {
 *     "canonical" = "view.wallet_statement.page_1",
 *     "admin-form" = "mcapi.wallets"
 *   }
 * )
 */
class Wallet extends ContentEntityBase {

  /**
   * I have a feeling that wallets should only belong to content entities.
   */

  private $owner;

  public static function postLoad(EntityStorageControllerInterface $storage_controller, array &$entities) {
    foreach ($entities as $wallet) {
      $parent_type = $wallet->get('entity_type')->value;
      //they could all have different parent entity types, so we have to load them separately
      if ($parent_type == 'system') {
        $wallet->owner = NULL;// new \Drupal\mcapi\Entity\Bank;
      }
      else {
        //@todo remove this array syntax
        $wallet->owner = entity_load('user', $wallet->get('pid')->value);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function uri() {
    return 'wallet/'.$this->id();
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->get('wid')->value;
  }

  /**
   * return the parent entity if there is one, otherwise return the wallet itself
   * the only reason there might not be an owner is if this is a system wallet
   */
  public function getOwner() {
    if ($this->owner) return $this->owner;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function label($langcode = NULL) {
    //to prevent recursion we don't call getOwner.
    if ($this->owner) {
      //if there is only one wallet per parent, we use the parent's name instead of the wallet
      if (max(\Drupal::config('mcapi.wallets')->get('entity_types')) < 2) {
        return $this->owner->label();
      }
      else {
        //Conjunction of parent entity label and the wallet name
        return t('!parent: !wallet_name', array('!parent' => $this->getOwner()->label(), '!wallet_name' => $this->get('name')->value));
      }
    }
    else {
      //if the wallet has no owner (the system is the owner), return just the wallet's name
      return $this->get('name')->value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageControllerInterface $storage_controller, array &$values) {
    //trying to set this as a default but not working
    $values['access_view'] = 'inherit';
    $values['access_payer'] = 'autheticated';
    $values['access_payer'] = 'current';
    parent::preCreate($storage_controller, $values);
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
}
