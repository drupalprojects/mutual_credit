<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\CurrencyType\Lollipops.
 */

namespace Drupal\mcapi\Plugin\CurrencyType;

use Drupal\mcapi\CurrencyTypeInterface;

/**
 * Creates a currency based upon time.
 *
 * @CurrencyType(
 *   id = "lollipops",
 *   label = @Translation("Lollipops"),
 *   description = @Translation("Currency based upon lollipops")
 * )
 */
class Lollipops extends Decimal implements CurrencyTypeInterface {

}