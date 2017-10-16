<?php

/**
 * @file
 */
namespace Drupal\mcapi\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Get a property from the supplied object
 *
 * @MigrateProcessPlugin(
 *   id = "d7_mcapi_object_prop"
 * )
 */
class ObjectProperty extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $prop_name = $this->configuration['property'];
    $result = $value->{$prop_name};
    if ($prop_name == 'path') {
      // I couldn't get concat to work
      $result = '/'.$result;
    }
    return $result;
  }

}
