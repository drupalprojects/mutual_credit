<?php

namespace Drupal\mcapi_exchanges\Plugin\DevelGenerate;

use Drupal\mcapi\Storage\TransactionStorage;
use Drupal\mcapi\Plugin\DevelGenerate\TransactionDevelGenerate;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\Group;
use Drupal\Core\Form\FormStateInterface;

/**
 * Replaces the TransactionDevelGenerate plugin.
 *
 * @DevelGenerate(
 *   id = "mcapi_exchange_transaction",
 *   label = @Translation("Transactions in exchanges"),
 *   description = @Translation("Generate transactions between wallets in the same exchanges"),
 *   url = "transaction",
 *   permission = "administer devel_generate",
 *   settings = {
 *     "num" = 200,
 *     "kill" = TRUE
 *   }
 * )
 */
class ExchangeTransactionDevelGenerate extends TransactionDevelGenerate {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    drupal_set_message("This only works once users have been added to exchanges.");
    return parent::settingsForm($form, $form_state);
  }

  /**
   * Create one transaction. Used by both batch and non-batch code branches.
   *
   * @note this may attempt to send a email for pending transactions.
   */
  public function develGenerateTransactionAdd(&$values) {
    parent::develGenerateTransactionAdd($values);
    if ($transaction = $this->lastTransaction() ){
      $props = [
        'gid' => $this->exchange_id,
        'type' => 'exchange-transactions',
        'entity_id' => $transaction->id(),
      ];
      GroupContent::create($props)->save();
    }
  }

  /**
   * Get two random wallets
   *
   * @param array $conditions
   *   Conditions for the wallet entityquery
   *
   * @return int[]
   *   2 wallets whose holders share an exchange.
   */
  public function get2RandWalletIds(array $conditions = []) {
    $exids = $this->getEntityQuery('group')->condition('type', 'exchange')->execute();
    $exchange = Group::load($exids[array_rand($exids)]);
    $wids = wallets_in_exchange($exchange);
    shuffle($wids);
    if (count($wids) < 2) {
      $msg = 'Only '.count($wids).' wallets in exchange: '.$exchange->id().': '.print_r($wids, 1);
      \Drupal::logger('mcapi')->warning($msg);
      return [];
    }
    $this->exchange_id = $exchange->id();
    return array_slice($wids, -2);
  }

  /**
   * Retrive the last created transaction (with the highest ID)
   *
   * @return \Drupal\mcapi\Entity\Transaction | NULL
   */
  protected function lastTransaction() {
    $serials = $this->getEntityQuery('mcapi_transaction')
      ->range(0, 1)
      ->sort('xid', 'DESC')
      ->execute();
    if ($serials) {
      return TransactionStorage::loadBySerial(reset($serials));
    }
  }

}
