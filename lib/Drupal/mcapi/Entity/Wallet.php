<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\Wallet.
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Field\FieldDefinition;

/**
 * Defines the wallet entity.
 *
 * @EntityType(
 *   id = "mcapi_wallet",
 *   label = @Translation("Wallet"),
 *   module = "mcapi",
 *   controllers = {
 *     "storage" = "Drupal\mcapi\WalletStorageController",
 *     "view_builder" = "\Drupal\Core\Entity\EntityViewBuilder",
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
 *     "admin-form" = "mcapi.wallets"
 *   }
 * )
 */
class Wallet extends ContentEntityBase {

  /**
   * the entity types which can receive wallets.
   * perhaps this should be added via hook_entity_info_alter to the entity definitions
   * $data['node']['wallet'] = TRUE
   * $data['user']['wallet'] = TRUE
   * maybe this variable isn't needed at all, or it could be a cached object or even a config setting
   * since wallets can never be deleted what we need to now is to which entities we can now add wallets.
   */
  var $entity_types;

  public function __construct($params, $entity_type, $definition) {
    parent::__construct($params, $entity_type, $definition);
    //for now...
    $this->entity_types = array('user', 'node');
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->get('wid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function label($langcode = NULL) {
    //get the parent entity's name and associate it with the wallet.
    $parent = $this->parent()->label();
    if (count($this->parent()->wallets) < 2) {
      return $parent;
    }
    else {
      return t('!parent: !wallet_name', array('!parent' => $parent, '!wallet_name' => $this->name->value));
    }
  }


  /**
   * {@inheritdoc}
   */
  public static function ______preCreate(EntityStorageControllerInterface $storage_controller, array &$values) {

    $values['access'] = 'inherit';//seems to make no difference
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
    //an entity reference field requires that the entity type be put in its settings.
    //but this entity reference field could reference any of several types
    //so I've created field here for entity_type and pid and we're not using the entity reference fieldtype at all.
    //Do we need to define a new entity reference field type which reads two properties of this entity?
    //or perhaps an alternative storage controller for the entity reference field?
    $properties['pid'] = FieldDefinition::create('integer')
      ->setLabel('Parent entity ID')
      ->setRequired(TRUE);
    $properties['name'] = FieldDefinition::create('string')
      ->setLabel('Name')
      ->setDescription("The owner's name for this wallet")
      ->setRequired(TRUE);
    $properties['access'] = FieldDefinition::create('string')
      ->setLabel('Access controller')
      ->setDescription("The class which controls access to this wallet")
      ->setRequired(TRUE);
    //similar to above this is an entity reference multiple field, so it can't be stored in an integer field in the wallet table...
    $properties['access'] = FieldDefinition::create('entity_reference')
      ->setLabel('Trusted proxies')
      ->setDescription("Other users who can trade from this wallet")
      ->setSettings(array('target_type' => 'user'));

    //+ a field to store the access control settings may be needed.
    return $properties;
  }
}
