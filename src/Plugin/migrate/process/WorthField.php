<?php

namespace Drupal\mcapi\Plugin\migrate\process;

use Drupal\mcapi\Entity\Currency;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Transform the worth value from a decimal with 2 places into an integer.
 *
 * @MigrateProcessPlugin(
 *   id = "mcapi_worth"
 * )
 */
class WorthField extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // At this point value is a decimal
    $decimal = $value['quantity'];
    $format = Currency::load($value['currcode'])->format;
    $quantity = intval($decimal);
    if (isset($format[3])) {
      if ($format[3] == '59/4') {
        $quantity = 60*$decimal;
      }
      elseif($format[3] == 99) {
        $quantity = 100*$decimal;
      }
    }
    return [
      'curr_id' => $value['currcode'],
      'value' => $quantity
    ];
  }

}
