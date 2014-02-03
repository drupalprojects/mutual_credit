<?php

/**
 * @file
 * Contains \Drupal\mcapi\Annotation\Limits.
 */

namespace Drupal\mcapi_limits\Annotation;

use Drupal\Core\TypedData\Annotation\DataType;

/**
 * Defines a 'limits' plugin annotation object.
 *
 * @Annotation
 */
class Limits extends DataType {

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
