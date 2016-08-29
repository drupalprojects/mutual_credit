<?php

namespace Drupal\mcapi\Plugin\DevelGenerate;

use Drupal\mcapi\Mcapi;
use Drupal\mcapi\Entity\Transaction;
use Drupal\mcapi\Entity\Wallet;
use Drupal\devel_generate\DevelGenerateBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a DevelGenerate plugin.
 *
 * @DevelGenerate(
 *   id = "mcapi_transaction",
 *   label = @Translation("transactions"),
 *   description = @Translation("Generate a given number of transactions..."),
 *   url = "transaction",
 *   permission = "administer devel_generate",
 *   settings = {
 *     "num" = 200,
 *     "kill" = TRUE
 *   }
 * )
 */
class TransactionDevelGenerate extends DevelGenerateBase implements ContainerFactoryPluginInterface {

  /**
   * The transaction storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $transactionStorage;

  /**
   * The database.
   */
  protected $database;

  /**
   * The entityQueryFactory.
   */
  protected $entityQueryFactory;

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
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->transactionStorage = $transaction_storage;
    $this->database = $database;
    $this->entityQueryFactory = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('entity.manager')->getStorage('mcapi_transaction'),
      $container->get('database'),
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['kill'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('<strong>Delete all transactions</strong> before generating new content.'),
      '#default_value' => $this->getSetting('kill'),
    );
    $form['num'] = array(
      '#type' => 'number',
      '#title' => $this->t('How many transactions would you like to generate?'),
      '#default_value' => $this->getSetting('num'),
      '#required' => TRUE,
      '#min' => 0,
    );
    $form['type'] = array(
      '#title' => $this->t('What type of transactions'),
      '#type' => 'select',
      '#options' => Mcapi::entityLabelList('mcapi_type'),
      '#default_value' => $this->getSetting('type'),
      '#required' => TRUE,
      '#min' => 0,
    );

    $form['#redirect'] = FALSE;

    if (!$this->enoughWallets()) {
      $form_state->setErrorByName('', 'Not enough wallets');
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function generateElements(array $values) {
    if ($values['num'] <= 100) {
      $this->generateContent($values);
    }
    else {
      $this->generateBatchContent($values);
    }
  }

  /**
   * Method responsible for creating a small number of transactions.
   *
   * @param array $values
   *   Kill, num, first_transaction_time
   *
   * @throws \Exception
   */
  public function generateContent($values) {
    if (!empty($values['kill'])) {
      $this->contentKill($values['type']);
    }
    //$curr_ids = array_keys(Currency::loadMultiple());
    for ($i = 1; $i <= $values['num']; $i++) {
      $this->develGenerateTransactionAdd($values);
    }
    $this->sortTransactions();
    if (function_exists('drush_log') && $i % drush_get_option('feedback', 1000) == 0) {
      drush_log(dt('Completed @feedback transactions ', ['@feedback' => drush_get_option('feedback', 1000)], 'ok'));
    }

  }

  /**
   * Generate batch to create large numbers of transactions.
   */
  private function generateBatchContent($values) {
    // Start the batch.
    $batch = [
      'title' => $this->t('Generating Transactions'),
      'operations' => [],
      'file' => drupal_get_path('module', 'devel_generate') . '/devel_generate.batch.inc',
    ];
    // Add the kill operation.
    if ($values['kill']) {
      $batch['operations'][] = [
        'devel_generate_operation',
        [$this, 'batchContentKill', $values['type']],
      ];
    }
    $total = $values['num'];
    $values['num'] = 100;
    // Add the operations to create the transactions.
    for ($num = 0; $num < floor($total / 100); $num++) {
      $values['batch'] = $num;
      $batch['operations'][] = [
        'devel_generate_operation',
        [$this, 'batchContentAddTransaction', $values],
      ];
    }
    if ($num = $total % 100) {
      // Add the remainder.
      $values['num'] = $total % 100;
      $values['batch'] = $num++;
      $batch['operations'][] = [
        'devel_generate_operation',
        [$this, 'batchContentAddTransaction', $values],
      ];
    }
    $batch['operations'][] = [[$this, 'sortTransactions']];
    batch_set($batch);
  }

  /**
   * Create one transaction as part of a batch.
   */
  public function batchContentAddTransaction($vars, &$context) {
    $context['results']['num'] = intval($context['results']['num']);
    for ($num = 0; $num < $vars['num']; $num++) {
      $this->develGenerateTransactionAdd($context['results']);
      $context['results']['num']++;
    }
  }

  /**
   * Delete previous transactions before creating a batch of them.
   */
  public function batchContentKill($values, &$context) {
    $this->contentKill($values['type']);
  }

  /**
   * {@inheritdoc}
   */
  public function validateDrushParams($args) {
    if (!$this->enoughWallets()) {
      return drush_set_error('DEVEL_GENERATE_INVALID_INPUT', dt('Not enough wallets to trade.'));
    }
    $values['kill'] = drush_get_option('kill');
    $values['type'] = drush_get_option('type');
    $values['num'] = array_shift($args);
    return $values;
  }

  /**
   * Deletes all transactions .
   *
   * @param array $values
   *   The input values from the settings form.
   *
   * @note Loads all transactions into memory at the same time.
   */
  protected function contentKill($type) {
    $xids = $this->entityQueryFactory->get('mcapi_transaction')
      ->condition('type', $type)
      ->execute();
    if (!empty($xids)) {
      $transactions = Transaction::loadMultiple($xids);
      $this->transactionStorage->delete($transactions);
      $this->setMessage($this->t('Deleted %count transactions.', array('%count' => count($xids))));
    }
  }

  /**
   * Create one transaction. Used by both batch and non-batch code branches.
   *
   * @note this may attempt to send a email for pending transactions.
   */
  public function develGenerateTransactionAdd(&$values) {
\Drupal::logger('groupcontent')->notice('Creating transaction');
    $values += ['conditions' => []];
    list($w1, $w2) = $this->get2RandWalletIds($values['conditions']);
    if (!$w2) {
      return;
    }
    $props = [
      'payer' => $w1,
      'payee' => $w2,
      // Transactions of type 'auto' don't show in the default view.
      'type' => $this->getSetting('type') ?: 'default',
      'creator' => 1,
      'description' => $this->getRandom()->sentences(1),
      'uid' => $w1
    ];

    // find a currency that's common to both wallets.
    $payer_currencies = Wallet::load($props['payer'])->currenciesAvailable();
    $payee_currencies = Wallet::load($props['payee'])->currenciesAvailable();

    $curr_ids = array_intersect_key($payer_currencies, $payee_currencies);
    $currency = $curr_ids[array_rand($curr_ids)];
    $props['worth'] = [
      'curr_id' => $currency->id(),
      'value' => $currency->sampleValue()
    ];

\Drupal::logger('groupcontent')->notice(print_r($props, 1));
    $transaction = Transaction::create($props);
    // We're not using generateExampleData here because it makes a mess.
    // But that means we might miss other fields on the transaction.

    // Change the created time of the transactions, coz they mustn't be all in
    // the same second.
    $transaction->save();
    if ($transaction->state->target_id == 'pending') {
      // Signatures already exist because they were created in presave phase.
      foreach ($transaction->signatures as $uid => $signed) {
        // Leave 1 in 10 signatures unsigned.
        if (rand(0, 9) > 0) {
          \Drupal::service('mcapi.signatures')->setTransaction($transaction)->sign($uid);
        }
      }
    }
    $transaction->created->value = $this->randTransactionTime($w1, $w2);
    // NB this could generate pending emails.
    $transaction->save();
  }

  /**
   * Get two random wallets
   *
   * @param array $conditions
   *   Conditions for the wallet entityquery
   *
   * @return int[]
   *   2 wallet ids
   */
  public function get2RandWalletIds(array $conditions = []) {
    $query = $this->entityQueryFactory->get('mcapi_wallet')
      ->condition('payways', Wallet::PAYWAY_AUTO, '<>');
    foreach ($conditions as $field => $value) {
      $query->condition($field, $value, is_array($value) ? 'IN' : '=');
    }
    $wids = $query->execute();
    if (!count($wids) < 2) {
      \Drupal::logger('mcapi')->warning('Not enough wallets in exchange: '.print_r($conditions, 1));
      return [];
    }
    shuffle($wids);
    return array_slice($wids, -2);
  }

  /**
   * Find some suitable wallets.
   *
   * @return Bool
   *   TRUE if there are least 2 wallets, excluding the intertrading wallet.
   */
  public function enoughWallets() {
    static $wallet_ids;
    if (!isset($wallet_ids)) {
      $wallet_ids = $this->entityQueryFactory('mcapi_wallet')
        ->condition('payways', Wallet::PAYWAY_AUTO, '<>')
        ->execute();
    }
    return count($wallet_ids) >= 2;
  }


  /**
   * Get a time that a transaction could have taken place between 2 wallets
   * @param type $wid1
   *   The first wallet ID.
   * @param type $wid2
   *   The second wallet ID.
   * @return integer
   *   The unixtime
   */
  public function randTransactionTime($wid1, $wid2) {
    //get the youngest wallet and make a time between its creation and now.
    $wallets = Wallet::loadMultiple([$wid1, $wid2]);
    $latest = max($wallets[$wid1]->created->value, $wallets[$wid2]->created->value);
    return rand($latest, REQUEST_TIME);
  }

  public function sortTransactions() {
    $times = $this->database->select('mcapi_transaction', 't')
      ->fields('t', ['serial', 'created'])
      ->execute()->fetchAllKeyed();
    $keys = array_keys($times);
    sort($keys);
    sort($times);
    $new = array_combine($keys, $times);
    foreach ($new as $serial => $created) {
      //assuming that $created is unique and clashes are extremely unlikely
      $this->database->update('mcapi_transaction')->fields(['serial' => $serial])->condition('created', $created)->execute();
      $this->database->update('mcapi_transactions_index')->fields(['serial' => $serial])->condition('created', $created)->execute();
    }
  }

}
