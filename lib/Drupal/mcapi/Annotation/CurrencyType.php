<?php

/**
 * @file
 * Contains \Drupal\mcapi\Annotation\CurrencyType.
 */

namespace Drupal\mcapi\Annotation;

use Drupal\Core\TypedData\Annotation\DataType;

/**
 * Defines a CurrencyType annotation object.
 *
 * @Annotation
 */
class CurrencyType extends DataType {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the module providing the field type plugin.
   *
   * @var string
   */
  public $module;

  /**
   * The human-readable name of the field type.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * A short human readable description for the field type.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

}