<?php

namespace Drupal\mcapi\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Transform the worth value from a decimal with 2 places into an integer.
 *
 * @MigrateProcessPlugin(
 *   id = "worth"
 * )
 */
class Worth extends ProcessPluginBase {

  private $map = [];

  /**
   * {@inheritdoc}
   * Transform the user id from the d7 ledger to a wallet id for the d8 ledger, creating a wallet if necessary.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    // Bit ugly but what to do if $value is not set?
    $quantity = $row->getSourceProperty($destination_property);
    echo $quantity;
    return $value;
  }

}
