<?php

namespace Drupal\mcapi\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;

/**
 * Defines the 'wallet' entity field type.
 *
 * Differs from entity_reference because it has a separate widget and
 * autocomplete class.
 *
 * @FieldType(
 *   id = "burser_reference",
 *   label = @Translation("Bursers"),
 *   description = @Translation("The first burser is always the wallet owner"),
 *   category = @Translation("Community Accounting"),
 *   default_formatter = "entity_reference_label",
 *   default_widget = "burser_reference_autocomplete",
 *   list_class = "\Drupal\mcapi\Plugin\Field\FieldType\BurserReferenceFieldItemList"
 * )
 */
class BurserItem extends EntityReferenceItem {

}
