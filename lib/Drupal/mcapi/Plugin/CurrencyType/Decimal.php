<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\CurrencyType\Decimal.
 */

namespace Drupal\mcapi\Plugin\CurrencyType;

use Drupal\mcapi\CurrencyTypeInterface;

/**
 * Creates a currency based upon time.
 *
 * @CurrencyType(
 *   id = "decimal",
 *   label = @Translation("Decimal"),
 *   description = @Translation("Standard numeric currency")
 * )
 */
class Decimal implements CurrencyTypeInterface {

}