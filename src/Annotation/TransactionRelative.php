<?php

namespace Drupal\mcapi\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a user relative to a Transaction entity.
 *
 * @Annotation
 */
class TransactionRelative extends Plugin {

  public $id;

  public $label;

  public $description;

  public $weight;

}
