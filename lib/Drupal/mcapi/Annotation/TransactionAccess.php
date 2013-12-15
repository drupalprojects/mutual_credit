<?php

/**
 * @file
 * Contains \Drupal\mcapi\Annotation\TransactionAccess.
 */

namespace Drupal\mcapi\Annotation;

use Drupal\Core\TypedData\Annotation\DataType;

/**
 * Defines a TransactionAccess annotation object.
 *
 * @Annotation
 */
class TransactionAccess extends DataType {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The default human-readable name of the field type.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

}
