<?php

/**
 * @file
 * Contains \Drupal\mcapi\Annotation\Operation.
 */

namespace Drupal\mcapi\Annotation;

use Drupal\Core\TypedData\Annotation\DataType;

/**
 * Defines a Transaction Operation annotation object.
 *
 * @Annotation
 */
class Operation extends DataType {

  public $id;

  public $label;

  public $description;

  public $settings;

}
