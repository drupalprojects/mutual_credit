<?php

/**
 * @file
 */
namespace Drupal\mcapi\Plugin\migrate\process;

use Drupal\mcapi\Entity\Currency;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\Core\Database\Database;

/**
 * Transform the worth value from a decimal with 2 places into an integer.
 *
 * @MigrateProcessPlugin(
 *   id = "d7_mcapi_worth_db"
 * )
 */
class Worth extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   * Take the xid and return the worth value.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $database_info = $row->getSourceProperty('datababase');
    $key = $row->getSourceProperty('key');
    $target = @$database_info['target'] ?: 'default';
    Database::addConnectionInfo($key, $target, $database_info['database']);
    $connection = Database::getConnection($target, $key);

    $result = $connection->select('field_data_worth', 'w')
      ->fields('w', ['worth_currcode', 'worth_quantity'])
      ->condition('entity_id', $value)
      ->condition('entity_type', 'transaction')
      ->execute()->fetchAllKeyed();
    $items = [];
    // At this point value is a decimal
    foreach ($result as $currcode => $decimal) {
      // Its too hard to convert the value fully automaticallly
      // if the currency is hours, convert the value to minutes.
      // We know hours because it has /59 in the format.
      $format = Currency::load($currcode)->format;
      if ($format[3] = '59/4') {
        $value = 60*$decimal;
      }
      elseif($format[3] == 99) {
        $value = 100*$decimal;
      }
      else {
        $value = intval($decimal);
      }
      $items[] = [
        'curr_id' => $currcode,
        'value' => $value
      ];
    }
    return $items;
  }

}
