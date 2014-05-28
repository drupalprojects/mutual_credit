<?php

/**
 * @file
 * Contains \Drupal\mcapi\Annotation\Transition.
 */

namespace Drupal\mcapi\Annotation;

use Drupal\Core\TypedData\Annotation\DataType;

/**
 * Defines a Transaction Transition annotation object.
 *
 * @Annotation
 */
class Transition extends DataType {

  public $id;

  public $label;

  public $description;

  public $settings;

}
