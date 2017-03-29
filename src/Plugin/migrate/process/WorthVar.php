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
class Worth extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   * Transform something of the format
   * [
   *   0 => [
   *     [currcode] => credunit
   *     [main_quant] => 4
   *     [div_quant] => 0
   *   ]
   * ]
   * to
   * [
   *   0 => [
   *     [curr_id] => credunit
   *     [value] ==> 400
   *   ]
   * ]
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    foreach ($value as $delta => $item) {
      $newValue[$delta]['curr_id'] = $item['currcode'];
      $parts = [1 => $item['main_quant']];
      $currency = Currency::load($item['currcode']);
      if (isset($currency->format[3])) {
        $parts[3] = $item['div_quant'];
      }
      $newValue[$delta]['value'] = $currency->unformat([0 => $item['main_quant'], 3 => $item['div_quant']]);
    }
    return $newValue;
  }

}
