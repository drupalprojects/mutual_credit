<?php

namespace Drupal\mcapi_limits\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a 'limits' plugin annotation object.
 *
 * @Annotation
 */
class Limits extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  public $label;

  public $description;

}
