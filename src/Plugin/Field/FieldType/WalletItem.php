<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldType\WalletItem.
 */

namespace Drupal\mcapi\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;


/**
 * Defines the 'wallet' entity field type, which differs from
 * entity_reference because it has a separate widget and autocomplete class
 *
 * @FieldType(
 *   id = "wallet",
 *   label = @Translation("Wallets"),
 *   description = @Translation("Selector for wallets you can pay in/out of"),
 *   category = @Translation("Reference"),
 *   no_ui = TRUE,
 *   default_formatter = "entity_reference_label",
 *   default_widget = "wallet_reference_autocomplete",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
 * )
 */
class WalletItem extends EntityReferenceItem {


  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    //these are overridden in BaseFieldDefinition
    return array(
      'target_type' => 'mcapi_wallet',
    ) + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    //these are overridden in BaseFieldDefinition
    return array(
      'handler' => 'default:wallet',
      'handler_settings' => [
        'op' => '*** REPLACE THIS ***'
      ],
    ) + parent::defaultFieldSettings();
  }


}
