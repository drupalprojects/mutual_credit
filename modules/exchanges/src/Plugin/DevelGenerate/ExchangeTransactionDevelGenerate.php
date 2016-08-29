<?php

namespace Drupal\mcapi_exchanges\Plugin\DevelGenerate;

use Drupal\mcapi\Entity\Transaction;
use Drupal\mcapi_exchanges\Exchanges;
use Drupal\mcapi\Plugin\DevelGenerate\TransactionDevelGenerate;
use Drupal\group\Entity\GroupContent;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Replaces the TransactionDevelGenerate plugin.
 *
 * @DevelGenerate(
 *   id = "mcapi_exchange_transaction",
 *   label = @Translation("transactions"),
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
   * Group IDs of all exchange groups
   *
   * @var array
   */
  protected $exids;

  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityStorageInterface $transaction_storage, $database, $entity_query) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $transaction_storage, $database, $entity_query);
    $this->exids = $entity_query->get('group')->condition('type', 'exchange')->execute();
  }


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
    $transaction = $this->lastTransaction();
    $props = [
      'gid' => $this->exchange_id,
      'type' => 'exchange-transactions',
      'entity_id' => $transaction->id(),
    ];
    GroupContent::create($props)->save();
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
    $this->exchange_id = $this->exids[array_rand($this->exids)];
    $wids = Exchanges::walletsInExchange([$this->exchange_id]);

    shuffle($wids);
    \Drupal::logger('mcapi')->debug(implode(', ', array_slice($wids, -2)));
    return array_slice($wids, -2);
  }


  protected function lastTransaction() {
    $serials = $this->entityQuery->get('mcapi_transaction')
      ->range(0, 1)
      ->sort('serial', 'DESC')
      ->execute();
    return Transaction::loadBySerial(reset($serials));
  }

}
