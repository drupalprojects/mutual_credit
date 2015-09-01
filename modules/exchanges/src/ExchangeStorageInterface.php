<?php

/**
 * @file
 * Contains \Drupal\mcapi\Storage\ExchangeStorageInterface.
 */

namespace Drupal\mcapi\Storage;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityInterface;

interface ExchangeStorageInterface extends EntityStorageInterface {

  /**
   * identify the exchanges using the given currency
   * @param CurrencyInterface $currency
   * @return array
   *   ids of the exchanges
   */
  function using_currency(CurrencyInterface $currency);

}
