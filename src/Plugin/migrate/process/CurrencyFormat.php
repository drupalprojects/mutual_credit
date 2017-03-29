<?php

/**
 * @file
 */
namespace Drupal\mcapi\Plugin\migrate\process;

use Drupal\mcapi\Form\CurrencyForm;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * convert the old display setting into the new one-string format
 *
 * @MigrateProcessPlugin(
 *   id = "d7_mcapi_currency_format"
 * )
 */
class CurrencyFormat extends ProcessPluginBase {

  /**
   * Constants taken from D7
   */
  const CURRENCY_DIVISION_MODE_NONE = 0;
  const CURRENCY_DIVISION_MODE_CENTS_INLINE = 1;
  const CURRENCY_DIVISION_MODE_CENTS_FIELD = 2;
  const CURRENCY_DIVISION_MODE_CUSTOM = 3;

  /**
   * {@inheritdoc}
   *
   * Division settings is a string with key|value pairs on each line
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if ($value->divisions == self::CURRENCY_DIVISION_MODE_NONE) {
      $format = '9999';
    }
    elseif($value->divisions == self::CURRENCY_DIVISION_MODE_CENTS_INLINE or $value->divisions == self::CURRENCY_DIVISION_MODE_CENTS_INLINE) {
      $format = '999'. $value->delimiter .'99';
    }
    elseif ($value->divisions == self::CURRENCY_DIVISION_MODE_CUSTOM){
      $items = explode("\n", $value->divisions_setting);
      $format = '99'.$value->delimiter.'59/'.count($items);
      preg_match('/.*|[0-9]+(.*)$/', end($items), $matches);
      if ($matches[1]) {
        $format .= ' '.$matches[1];
      }
    }
    $string = str_replace('[quantity]', $format, $value->format);
    return CurrencyForm::transformFormat($string);
  }

}
