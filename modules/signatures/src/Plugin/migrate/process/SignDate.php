<?php

/**
 * @file
 * Contains \Drupal\mcapi_signatures\Plugin\migrate\process\SignDate.
 */

namespace Drupal\mcapi_signatures\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\mcapi\Entity\Wallet;
use Drupal\user\Entity\User;
use Drupal\mcapi\Mcapi;

/**
 * check the payer or payee user has a wallet and swop the uid for the wallet id
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

    $pending = $row->getSourceProperty($destination_property);//bit ugly but what to do if $value is not set?
    //a value of 1 means the transaction is pending
    if ($pending == 1) {
      return 0;//that means it is not signed
    }
    elseif($pending == 0) {
      //we have to give a date that the transaction was signed. Using the transaction created date
      return $row->getSourceProperty('created');
    }
  }

}
