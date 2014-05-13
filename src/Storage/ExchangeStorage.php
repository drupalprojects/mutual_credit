<?php

/**
 * @file
 * Contains \Drupal\mcapi\Storage\ExchangeStorage.
 */

namespace Drupal\mcapi\Storage;

use Drupal\Core\Entity\ContentEntityDatabaseStorage;
use Drupal\mcapi\ExchangeInterface;

class ExchangeStorage extends ContentEntityDatabaseStorage {

  /**
   * {@inheritdoc}
   */
  function deletable(ExchangeInterface $exchange) {
    if ($exchange->get('active')->value) return FALSE;
    if (\Drupal::config('mcapi.misc')->get('indelible')) {
      return user_access('manage mcapi');
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  function deactivatable(ExchangeInterface $exchange) {
    static $active_exchange_ids = array();
    if (!$active_exchange_ids) {
      //get the names of all the open exchanges
      foreach (entity_load_multiple('mcapi_exchange') as $entity) {
        if ($exchange->get('open')->value) {
          $active_exchange_ids[] = $exchange->id();
        }
      }
    }
    if (count($active_exchange_ids) > 1)return TRUE;
    return FALSE;
  }

}
