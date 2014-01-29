<?php

/**
 * @file
 * Contains \Drupal\mcapi\ExchangeStorageController.
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\FieldableDatabaseStorageController;

class ExchangeStorageController extends FieldableDatabaseStorageController {

  /**
   * check if an exchange, and all the transactions in it can be deleted, which means all of
   * the exchange is already disabled (closed)
   * the delete mode allows its transactions to be deleted.
   *
   * @param EntityInterface $exchange
   * @return Boolean
   */
  function deletable(EntityInterface $exchange) {
    if ($exchange->get('open')->value) return FALSE;
    if (\Drupal::config('mcapi.misc')->get('indelible'))return FALSE;
    return TRUE;
  }

  /**
   * check if an exchange can be closed, which means
   * it is currently active
   * it is not the only open exchange
   *
   * @param EntityInterface $exchange
   * @return Boolean
   */
  function closable($exchange) {
    static $open_exchange_ids = array();
    if ($exchange->get('open')->value == FALSE) return FALSE;
    if (!$open_exchange_ids) {
      //get the names of all the enabled currencies
      foreach ($this->loadMultiple() as $entity) {
        if ($exchange->get('open')->value) {
          $open_exchange_ids[] = $entity->id();
        }
      }
    }
    if (count($open_exchange_ids) > 1)return TRUE;
    return FALSE;
  }
}
