<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Derivative\McapiLocalTask.
 */

namespace Drupal\mcapi\Plugin\Derivative;

//@todo remove these use statements
use Drupal\field_ui\Plugin\Derivative\FieldUiLocalTask;

/**
 * Provides local task definitions for all entity bundles.
 */
class McapiLocalTask extends FieldUiLocalTask {

  /**
   * {@inheritdoc}
   * @todo this function isn't called, in any order!
   * @see mcapi.links.task.yml
   */
  public function alterLocalTasks(&$local_tasks) {
    print_r(array_keys($local_tasks));die();

  }

}
