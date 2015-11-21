<?php

/**
 * @file
 * Contains \Drupal\mcapi\Annotation\TransactionRelative
 */

namespace Drupal\mcapi\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines a user relative to a Transaction entity.
 *
 * @Annotation
 */
class TransactionRelative extends Plugin {
  
  use StringTranslationTrait;

  public $id;

  public $label;

  public $description;
  
  public $weight;

}
