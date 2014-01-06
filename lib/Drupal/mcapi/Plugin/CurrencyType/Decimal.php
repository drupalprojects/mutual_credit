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
 * Creates a currency based upon time.
 *
 * @CurrencyType(
 *   id = "decimal",
 *   label = @Translation("Decimal"),
 *   description = @Translation("Standard numeric currency"),
 *   settings = {
 *     "scale" = 2
 *   },
 *   default_widget = "currency_decimal_single"
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
      '#options' => drupal_map_assoc(range(0, 10)),
      '#default_value' => $currency->settings['scale'],
      '#description' => t('The number of digits to the right of the decimal.'),
    );

    return $form;
  }

  /**
   * Format the currency.
   */
  public function format($value) {
    return number_format($value / pow(10, $this->getSetting('scale')), $this->getSetting('scale'));
  }
}