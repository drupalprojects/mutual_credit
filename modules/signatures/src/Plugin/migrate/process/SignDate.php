<?php

namespace Drupal\mcapi_signatures\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Check the payer or payee user has a wallet and swop the uid for the wallet id.
 *
 * @MigrateProcessPlugin(
 *   id = "sign_date"
 * )
 */
class SignDate extends ProcessPluginBase {

  private $map = [];

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    // Bit ugly but what to do if $value is not set?
    $pending = $row->getSourceProperty($destination_property);
    // A value of 1 means the transaction is pending.
    if ($pending == 1) {
      // That means it is not signed.
      return 0;
    }
    elseif ($pending == 0) {
      // We have to give a date that the transaction was signed. Using the transaction created date.
      return $row->getSourceProperty('created');
    }
  }

}
