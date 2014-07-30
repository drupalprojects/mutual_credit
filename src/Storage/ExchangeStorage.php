<?php

/**
 * @file
 * Contains \Drupal\mcapi\Storage\ExchangeStorage.
 * @todo make an interface for this
 */

namespace Drupal\mcapi\Storage;

use Drupal\Core\Entity\ContentEntityDatabaseStorage;
use Drupal\mcapi\Entity\ExchangeInterface;
use Drupal\mcapi\Entity\CurrencyInterface;
use Drupal\mcapi\Entity\Exchange;

class ExchangeStorage extends ContentEntityDatabaseStorage {

  /**
   * {@inheritdoc}
   */
  function deactivatable(ExchangeInterface $exchange) {
    static $active_exchange_ids = array();
    if (!$active_exchange_ids) {
      //get the names of all the open exchanges
      foreach (Exchange::loadMultiple() as $entity) {
        if ($exchange->get('status')->value) {
          $active_exchange_ids[] = $exchange->id();
        }
      }
    }
    if ($exchange->get('status')->value && count($active_exchange_ids) > 1)return TRUE;
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  function using_currency(CurrencyInterface $currency) {
    return db_select('mcapi_exchange__currencies', 'c')
      ->fields('c', array('entity_id'))
      ->condition('currencies_target_id', $currency->id())
      ->execute()->fetchCol();
  }

}
