<?php

/**
 * @file
 * Definition of Drupal\mcapi\CurrencyStorageController.
 */

namespace Drupal\mcapi;

use Drupal\Core\Config\Entity\ConfigStorageController;

class CurrencyStorageController extends ConfigStorageController {

  /**
   * check if a currency can be deleted, which means all of
   * it is already disabled (retired)
   * the delete mode allows its transactions to be deleted.
   *
   * @param CurrencyInterface $mcapi_currency
   * @return Boolean
   */
  function deletable($mcapi_currency) {
    if ($mcapi_currency->get('status')) return FALSE;
    if (\Drupal::config('mcapi.misc')->get('indelible'))return FALSE;
  }

  /**
   * check if a currency can be retired, which means
   * if is currently active
   * it is not the only unretired currency
   *
   *
   * @param CurrencyInterface $mcapi_currency
   * @return Boolean
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
