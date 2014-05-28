<?php

/**
 * @file
 * Contains \Drupal\mcapi\Storage\ExchangeStorage.
 */

namespace Drupal\mcapi\Storage;

use Drupal\Core\Entity\ContentEntityDatabaseStorage;
use Drupal\mcapi\Entity\ExchangeInterface;
use Drupal\mcapi\Entity\CurrencyInterface;

class ExchangeStorage extends ContentEntityDatabaseStorage {

  /**
   * {@inheritdoc}
   */
  function deletable(ExchangeInterface $exchange) {
    if ($exchange->get('status')->value) {
      $exchange->reason = t('Exchange must be disabled');
      return FALSE;
    }
    if (\Drupal::config('mcapi.misc')->get('indelible')) {
      $exchange->reason = t("Indelible Accounting flag is enabled.");
      return FALSE;
    }
    if (count($exchange->intertrading_wallet()->history())) {
      $exchange->reason = t('Exchange intertrading wallet has transactions');
      return FALSE;
    }
    //if the exchange has wallets, even orphaned wallets, it can't be deleted.
    $conditions = array('exchanges' => $exchange->id());
    $wallet_ids = \Drupal::EntityManager()->getStorage('mcapi_wallet')->filter($conditions);
    if (count($wallet_ids > 1)){
      $exchange->reason = t('The exchange still owns wallets: @nums', array('@nums' => implode(', ', $wallet_ids)));
      return FALSE;
    }
    if (!user_access('manage mcapi')) {
      $exchange->reason = t('You do not have permission');
      return FALSE;
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
        if ($exchange->get('status')->value) {
          $active_exchange_ids[] = $exchange->id();
        }
      }
    }
    if (count($active_exchange_ids) > 1)return TRUE;
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
