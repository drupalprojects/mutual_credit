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
 * Creates a currency based upon time.
 *
 * @CurrencyType(
 *   id = "time",
 *   label = @Translation("Time"),
 *   description = @Translation("Currency based upon time"),
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

  function format($quant, array $settings) {
    $hours = ($quant - ($quant % 3600)) / 3600;
    $minutes = ($quant - ($hours * 3600) - ($quant % 60)) / 60;
    $seconds = $quant % 60;
    return $hours . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ($seconds ? ':' . $seconds : '');
  }
}