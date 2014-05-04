<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\CurrencyType\Decimal.
 */

namespace Drupal\mcapi\Plugin\CurrencyType;

use Drupal\mcapi\CurrencyInterface;
use Drupal\mcapi\CurrencyTypeBase;
use Drupal\mcapi\CurrencyTypeInterface;

/**
 * Creates a currency based upon cents.
 *
 * @CurrencyType(
 *   id = "decimal",
 *   label = @Translation("Decimal"),
 *   description = @Translation("Standard numeric currency"),
 *   default_widget = "currency_decimal_single"
 *   settings = {
 *     scale = "2"
 *   }
 * )
 */
class Decimal extends CurrencyTypeBase implements CurrencyTypeInterface {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state, CurrencyInterface $currency) {
    $form['scale'] = array(
      '#type' => 'select',
      '#title' => t('Scale'),
      '#options' => array_combine(range(0, 10), range(0, 10)),
      '#default_value' => $currency->settings['scale'],
      '#description' => t('The number of digits to the right of the decimal.'),
    );

    return $form;
  }

  /**
   * Format the currency.
   */
  public function format($value) {
    return $this->decimal($value);
  }

  public function decimal($value) {
    return number_format($value / pow(10, $this->getSetting('scale')), $this->getSetting('scale'));
  }
}
