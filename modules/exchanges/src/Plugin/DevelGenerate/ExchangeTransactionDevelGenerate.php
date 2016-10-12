<?php

namespace Drupal\mcapi_exchanges\Plugin\DevelGenerate;

use Drupal\mcapi\Entity\Transaction;
use Drupal\mcapi_exchanges\Exchanges;
use Drupal\mcapi\Plugin\DevelGenerate\TransactionDevelGenerate;
use Drupal\group\Entity\GroupContent;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\Query\QueryFactory;
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
   * Entity Query Object
   *
   * @var Drupal\Core\Entity\Query\Sql\Query
   */
  protected $groupQuery;

  /**
   * Entity Query Object
   *
   * @var Drupal\Core\Entity\Query\Sql\Query
   */
  protected $groupContentQuery;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param array $plugin_definition
   *   Definition of the plugin.
   * @param \Drupal\Core\Entity\EntityStorageInterface $transaction_storage
   *   The transaction storage.
   * @param \Drupal\Core\Database\Connection
   *   The database connection
   * @param \Drupal\Core\Entity\Query\QueryFactory
   *   The query Factory
   *
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityStorageInterface $transaction_storage, Connection $database, QueryFactory $entity_query) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $transaction_storage, $database, $entity_query);
    $this->groupQuery = $entity_query->get('group');
    $this->groupContentQuery = $entity_query->get('group_content');
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
    $exids = $this->groupQuery->condition('type', 'exchange')->execute();
    $this->exchange_id = $exids[array_rand($exids)];
    $wids = Exchanges::walletsInExchanges([$this->exchange_id]);
    shuffle($wids);
    if (count($wids) < 2) {
      $msg = 'Only '.count($wids).' wallets in exchange: '.$this->exchange_id.': '.print_r($wids, 1);
      \Drupal::logger('mcapi')->warning($msg);
      return [];
    }
    return array_slice($wids, -2);
  }


  protected function lastTransaction() {
    $serials = $this->transactionQuery
      ->range(0, 1)
      ->sort('xid', 'DESC')
      ->execute();
    return Transaction::loadBySerial(reset($serials));
  }

}
