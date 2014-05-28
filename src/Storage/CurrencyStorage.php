<?php

/**
 * @file
 * Definition of Drupal\mcapi\Storage\CurrencyStorage.
 */

namespace Drupal\mcapi\Storage;

use Drupal\Core\Config\Entity\ConfigEntityStorage;

class CurrencyStorage extends ConfigEntityStorage {

  /**
   * check if a currency can be deleted, which means
   * there are no transactions using it.
   *
   * @param CurrencyInterface $mcapi_currency
   * @return Boolean
   *   TRUE if the currency is deletable
   */
  function deletable($mcapi_currency) {

    if (\Drupal::config('mcapi.misc')->get('indelible'))return FALSE;

    $all_transactions = $mcapi_currency->transactions(array('state' => 0));
    $deleted_transactions = $mcapi_currency->transactions(array('state' => 0));

    return $all_transactions == $deleted_transactions;
  }

}
