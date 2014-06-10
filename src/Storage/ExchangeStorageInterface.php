<?php

/**
 * @file
 * Contains \Drupal\mcapi\Storage\ExchangeStorageInterface.
 */

namespace Drupal\mcapi\Storage;

use Drupal\Core\Entity\FieldableEntityStorageInterface;
use Drupal\Core\Entity\EntityInterface;

interface ExchangeStorageInterface extends FieldableEntityStorageInterface {

  /**
   * check if an exchange can be deactivated, which means that it is not the only active exchange
   *
   * @param EntityInterface $exchange
   * @return Boolean
   */
  function deactivatable($exchange);

  /**
   * identify the exchanges using the given currency
   * @param CurrencyInterface $currency
   * @return array
   *   ids of the exchanges
   */
  function using_currency(CurrencyInterface $currency);

}
