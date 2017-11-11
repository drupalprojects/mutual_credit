<?php

/**
 * @file
 */
namespace Drupal\mcapi\Plugin\migrate\process;

use Drupal\mcapi\Entity\Currency;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Transform the worth value from a decimal with 2 places into an integer.
 *
 * @MigrateProcessPlugin(
 *   id = "d7_mcapi_worth_var"
 * )
 */
class WorthVar extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   * Transform something of the format
   * [
   *   [currcode] => credunit
   *   [main_quant] => 4
   *   [div_quant] => 0
   * ]
   * to
   * [
   *   [curr_id] => credunit
   *   [value] ==> 400
   * ]
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (is_array($value) and !empty($value['currcode'])) {//Might be null
      $currency = Currency::load($value['currcode']);
      if (!$currency) {
        throw new \Drupal\migrate\MigrateSkipRowException('Unknown currency '.$value['currcode']);
      }
      $newValue['curr_id'] = $value['currcode'];
      // There were some awkward stored values on d7
      if (isset($value['quantity'])) {
        if (strpos($value['quantity'], '.')) {
          $three = 0;
          list($one, $three) = explode('.', $value['quantity']);
        }
        else {
          $one = $value['quantity'];
        }
        $parts = [1 => $one, 3 => $three];
      }
      else {
        $parts = [
          1 => isset($value['main_quant']) ? $value['main_quant'] : 0,
          3 => isset($value['div_quant']) ? $value['div_quant'] : 0
        ];
      }
      $newValue['value'] = $currency->unformat($parts);
    }
    return $newValue;
  }

}
