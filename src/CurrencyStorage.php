<?php

/**
 * @file
 * Definition of Drupal\mcapi\CurrencyStorage.
 */

namespace Drupal\mcapi;

use Drupal\Core\Config\Entity\ConfigEntityStorage;

class CurrencyStorage extends ConfigEntityStorage {

  /**
   * check if a currency can be deleted, which means all of
   * it is already disabled (retired)
   * the delete mode allows its transactions to be deleted.
   *
   * @param CurrencyInterface $mcapi_currency
   * @return Boolean
   *   TRUE if the currency is deletable
   */
  function deletable($mcapi_currency) {
    if ($mcapi_currency->get('status')) return FALSE;
    if (\Drupal::config('mcapi.misc')->get('indelible'))return FALSE;
    return TRUE;
  }

  /**
   * check if a currency can be retired, which means
   * if is currently active
   * it is not the only unretired currency
   *
   *
   * @param CurrencyInterface $mcapi_currency
   * @return Boolean
   *   TRUE if the currency can be disabled
   */
  function disablable($mcapi_currency) {
    static $enabled_currcodes = array();
    if ($mcapi_currency->status == FALSE) return FALSE;
    if (!$enabled_currcodes) {
      //get the names of all the enabled currencies
      foreach ($this->loadMultiple() as $entity) {
        if ($entity->status){
          $enabled_currcodes[] = $entity->id();
        }
      }
    }
    if (count($enabled_currcodes) > 1)return TRUE;
    return FALSE;
  }

}
