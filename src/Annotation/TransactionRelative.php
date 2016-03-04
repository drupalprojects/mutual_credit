<?php

/**
 * @file
 * Contains \Drupal\mcapi\Annotation\TransactionRelative
 */

namespace Drupal\mcapi\Annotation;

/**
 * Defines a user relative to a Transaction entity.
 *
 * @Annotation
 */
class TransactionRelative extends \Drupal\Component\Annotation\Plugin {

  public $id;

  public $label;

  public $description;
  
  public $weight;

}
