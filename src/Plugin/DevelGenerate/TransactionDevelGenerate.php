<?php

namespace Drupal\mcapi\Plugin\DevelGenerate;

use Drupal\mcapi\Mcapi;
use Drupal\mcapi\Entity\Transaction;
use Drupal\mcapi\Entity\Currency;
use Drupal\mcapi\Entity\Wallet;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\devel_generate\DevelGenerateBase;
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
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityStorageInterface $transaction_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->transactionStorage = $transaction_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $entity_manager->getStorage('mcapi_transaction'),
      $container->get('date.formatter')
    );
  }

  /**
   * Find some suitable wallets.
   *
   * @staticvar type $wallet_ids
   *
   * @return integer[] or FALSE
   *   wallet ids, shuffled
   */
  public function prepareWallets() {
    static $wallet_ids;
    if (!isset($wallet_ids)) {
      $wallet_ids = \Drupal::entityQuery('mcapi_wallet')
        ->condition('holder_entity_type', 'user')
        ->execute();
    }
    shuffle($wallet_ids);
    return (count($wallet_ids) < 2) ?
      FALSE :
      $wallet_ids;

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

    $wids = $this->prepareWallets();
    if (!$wids) {
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
      $this->contentKill($values);
    }
    //$curr_ids = array_keys(Currency::loadMultiple());
    for ($i = 1; $i <= $values['num']; $i++) {
      $this->develGenerateTransactionAdd($values);
    }
    if (function_exists('drush_log') && $i % drush_get_option('feedback', 1000) == 0) {
      drush_log(dt('Completed @feedback transactions ', ['@feedback' => drush_get_option('feedback', 1000)], 'ok'));
    }
  }

  /**
   * Generate batch to create large numbers of transactions.
   */
  private function generateBatchContent($values) {
    // Add the kill operation.
    if ($values['kill']) {
      $operations[] = [
        'devel_generate_operation',
        [$this, 'batchContentKill', $values],
      ];
    }
    $total = $values['num'];
    $values['num'] = 100;
    // Add the operations to create the transactions.
    for ($num = 0; $num < floor($total / 100); $num++) {
      $values['batch'] = $num;
      $operations[] = [
        'devel_generate_operation',
        [$this, 'batchContentAddTransaction', $values],
      ];
    }
    if ($num = $total % 100) {
      // Add the remainder.
      $values['num'] = $total % 100;
      $values['batch'] = $num++;
      $operations[] = [
        'devel_generate_operation',
        [$this, 'batchContentAddTransaction', $values],
      ];
    }

    // Start the batch.
    $batch = [
      'title' => $this->t('Generating Transactions'),
      'operations' => $operations,
      'file' => drupal_get_path('module', 'devel_generate') . '/devel_generate.batch.inc',
    ];
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
  public function batchContentKill($vars, &$context) {
    $this->contentKill($context['results']);
  }

  /**
   * {@inheritdoc}
   */
  public function validateDrushParams($args) {
    $wids = $this->prepareWallets();
    if (!$wids) {
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
   * @note May consume a lot of memory
   */
  protected function contentKill($values) {
    $transactions = Transaction::loadMultiple();
    if (!empty($transactions)) {
      $this->transactionStorage->delete($transactions);
      $this->setMessage($this->t('Deleted %count transactions.', array('%count' => count($transactions))));
    }
  }

  /**
   * Create one transaction. Used by both batch and non-batch code branches.
   *
   * @note this may attempt to send a email for pending transactions.
   */
  public function develGenerateTransactionAdd(&$values) {
    $values += ['conditions' => []];
    $rand_wallet_ids = $this->getWallets((array)$values['conditions']);
    $props = [
      'payer' => $rand_wallet_ids[0],
      'payee' => $rand_wallet_ids[1],
    // Auto doesn't show in the default view.
      'type' => $this->getSetting('type') ?: 'default',
      'creator' => 1,
      'description' => $this->getRandom()->sentences(1),
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
    $transaction->created->value = $this->randTransactionTime($rand_wallet_ids[0], $rand_wallet_ids[1]);
    // NB this could generate pending emails.
    $transaction->save();
  }

  /**
   * Get two random wallets
   *
   * @param array $conditions
   *   Conditions for the wallet entityquery
   * @todo prevent
   */
  public function getWallets(array $conditions = []) {
    $query = \Drupal::entityQuery('mcapi_wallet')
      ->condition('payways', Wallet::PAYWAY_AUTO, '<>');
    foreach ($conditions as $field => $value) {
      $query->condition($field, $value, is_array($value) ? 'IN' : '=');
    }
    $wids = $query->execute();
    if (count($wids) < 2) {
      throw new \Exception('Not enough wallets to make a transaction.');
    }
    shuffle($wids);

    return array_slice($wids, -2);
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

}
