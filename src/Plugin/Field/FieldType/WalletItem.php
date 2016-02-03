<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldType\WalletItem.
 * @todo this doesn't need a list class, but much of it is coded in already
 */

namespace Drupal\mcapi\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;


/**
 * Defines the 'wallet' entity field type, which differs from
 * entity_reference because it has a separate widget and autocomplete class
 *
 *
 *
 * @FieldType(
 *   id = "wallet_reference",
 *   label = @Translation("Wallets"),
 *   description = @Translation("Context aware wallets you can pay in/out of"),
 *   category = @Translation("Reference"),
 *   default_formatter = "entity_reference_label",
 *   default_widget = "wallet_reference_autocomplete",
 *   list_class = "\Drupal\mcapi\Plugin\Field\FieldType\WalletItemList",
 * )
 */
class WalletItem extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   * @todo is this still needed?
   */
  public static function defaultFieldSettings() {
    //if target_type MUST be defined in Transaction::BaseFieldDefinition doesn't work here
    return [
      'handler' => 'default:mcapi_wallet',
      'handler_settings' => [
        'op' => '** REPLACE THIS **'
      ],
    ]+ parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return array(
      'target_type' => 'mcapi_wallet',
    ) + parent::defaultStorageSettings();
  }


  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    return [];
  }

}
