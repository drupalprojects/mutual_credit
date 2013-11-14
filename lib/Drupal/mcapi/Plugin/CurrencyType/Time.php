<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\CurrencyType\Time.
 */

namespace Drupal\mcapi\Plugin\CurrencyType;

use Drupal\mcapi\CurrencyTypeInterface;

/**
 * Creates a currency based upon time.
 *
 * @CurrencyType(
 *   id = "time",
 *   label = @Translation("Time"),
 *   description = @Translation("Currency based upon time")
 * )
 */
class Time implements CurrencyTypeInterface {

}