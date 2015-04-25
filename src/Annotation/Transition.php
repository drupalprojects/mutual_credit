<?php

/**
 * @file
 * Contains \Drupal\mcapi\Annotation\Transition.
 */

namespace Drupal\mcapi\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines a Transaction Transition annotation object.
 *
 * @Annotation
 */
class Transition extends Plugin {

  public $id;

}
