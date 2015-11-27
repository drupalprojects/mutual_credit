<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldType\WalletItem.
 */

namespace Drupal\mcapi\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;


/**
 * Defines the 'wallet' entity field type, which is only separate from
 * entity_reference because it has a separate widget and autocomplete class
 *
 * @FieldType(
 *   id = "wallet",
 *   label = @Translation("Wallets"),
 *   description = @Translation("Selector for wallets you can pay in/out of"),
 *   category = @Translation("Reference"),
 *   no_ui = TRUE,
 *   default_formatter = "entity_reference_label",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
 * )
 */
class WalletItem extends EntityReferenceItem {

}
