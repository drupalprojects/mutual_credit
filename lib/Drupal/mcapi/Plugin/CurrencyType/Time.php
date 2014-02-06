<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\CurrencyType\Time.
 */

namespace Drupal\mcapi\Plugin\CurrencyType;

use Drupal\mcapi\CurrencyInterface;
use Drupal\mcapi\CurrencyTypeBase;
use Drupal\mcapi\CurrencyTypeInterface;

/**
 * Creates a currency based upon time, multiplying seconds into minutes and hours.
 *
 * @CurrencyType(
 *   id = "time",
 *   label = @Translation("Time in seconds"),
 *   description = @Translation("Time shown as hours, minutes and seconds"),
 *   default_widget = "currency_time_single"
 * )
 */
class Time extends CurrencyTypeBase implements CurrencyTypeInterface {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state, CurrencyInterface $currency) {
    return array();
  }

  /*
   * Format the currency.
   */
  public function format($value) {
    $hours = ($value - ($value % 3600)) / 3600;
    $minutes = ($value - ($hours * 3600) - ($value % 60)) / 60;
    $seconds = $value % 60;

    return $hours . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ($seconds ? ':' . $seconds : '');
  }
}
