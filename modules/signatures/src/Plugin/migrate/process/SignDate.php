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
    // The source of this is the D7 pending field
    if ($value) {
      $value = 0;
    }
    else {
      // If the pending field was 0, then we replace it with the created date
      $value = $row->getSourceProperty('created');

      if ($row->getSourceProperty('uid') == $row->getSourceProperty('creator')) {
        $value += 86400;
      }
    }
    return $value;
  }

}
