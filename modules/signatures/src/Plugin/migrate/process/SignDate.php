<?php

namespace Drupal\mcapi_signatures\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Change the meaning of this field from TRUE = pending to int = date signed,
 * using the transaction changed date as default.
 *
 * @MigrateProcessPlugin(
 *   id = "mcapi_sign_date"
 * )
 */
class SignDate extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    if ($value) {
      $value = 0;
    }
    else {
      $value = $row->getSourceProperty('created');
    }
    return $value;
  }

}
