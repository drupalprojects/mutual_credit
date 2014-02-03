<?php

/**
 * @file
 * Contains \Drupal\mcapi\Annotation\WalletAccess.
 */

namespace Drupal\mcapi\Annotation;

use Drupal\Core\TypedData\Annotation\DataType;

/**
 * Defines a Wallet Access annotation object.
 *
 * @Annotation
 */
class WalletAccess extends DataType {

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

  public $description;

}
