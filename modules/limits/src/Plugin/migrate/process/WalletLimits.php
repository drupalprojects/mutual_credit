<?php

/**
 * @file
 */
namespace Drupal\mcapi_limits\Plugin\migrate\process;

use Drupal\mcapi\Storage\WalletStorage;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateSkipRowException;

/**
 * convert the old display setting into the new one-string format
 *
 * @MigrateProcessPlugin(
 *   id = "d7_mcapi_currency_user_limit_override"
 * )
 */
class WalletLimits extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   *
   * $user->data['limits_personal'][$currcode]['min'] = $min;
   * We convert it to go in its own table with fields
   * ['wid', 'curr_id', 'max', 'value', 'editor', 'date']
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    //would be the unserialized $user->data array from D7, except it doesn't come through because it is an array
    $value = $row->getSourceProperty('data');
    $uid = $row->getSourceProperty('uid');
    if (empty($value['limits_personal'])) {
      throw new MigrateSkipRowException();
    }
    foreach (WalletStorage::myWallets($uid) as $wid) {
      // Apply the same limits to every wallet held by the user
      // Although it seems highly unlikely that any user coming from D7 would have more than one wallet yet...
      foreach($value['limits_personal'] as $curr_id => $limits) {
        foreach ($limits as $limit => $value) {
          if (is_numeric($value)) {
            $result[] = [
              'wid' => $wid,
              'curr_id' => $curr_id,
              'max' => (int)$limit == 'max',
              'value' => $value,
              'editor' => 1, //User 1 because this field didn't exist in 7
              'date' => REQUEST_TIME
            ];
          }
        }
      }
    }
    return $result;
  }

}
