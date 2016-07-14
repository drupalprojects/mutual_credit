<?php

namespace Drupal\mcapi\Plugin\DevelGenerate;

use Drupal\mcapi\Mcapi;
use Drupal\mcapi\Entity\Transaction;
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
    $values['first_transaction_time'] =  $this->firstTransactionTime();
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
    $interval = $this->timing($values['first_transaction_time'], $values['num']);
    drupal_set_message(
      t(
        'Generating @num transactions starting on @date, every @count seconds',
        [
          '@num' => $values['num'],
          '@date' => date('d M Y', $values['first_transaction_time']),
          '@interval' => $interval,
        ]
      )
    );

    for ($i = 1; $i <= $values['num']; $i++) {
      $this->develGenerateTransactionAdd($values, $values['first_transaction_time'] + $interval * $i);
    }
    if (function_exists('drush_log') && $i % drush_get_option('feedback', 1000) == 0) {
      drush_log(dt('Completed @feedback transactions ', ['@feedback' => drush_get_option('feedback', 1000)], 'ok'));
    }
    $this->setMessage(t('Finished creating @count transactions', ['@count' => $values['num']]));
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
    $values['interval'] = $this->timing($values['first_transaction_time'], $values['num']);
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
      $created = $vars['first_transaction_time'] + $vars['interval'] * $num * $vars['batch'];
      $this->develGenerateTransactionAdd($context['results'], $created);
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
  public function develGenerateTransactionAdd(&$results, $time) {
    list($props['payer'], $props['payee']) = $this->getWallets($time);
    $props += [
    // Auto doesn't show in the default view.
      'type' => $this->getSetting('type') ?: 'default',
      'creator' => 1,
      'description' => $this->getRandom()->sentences(1),
    ];
    $transaction = Transaction::create($props);
    // Populate all fields with sample values.
    $this->populateFields($transaction);

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
    $transaction->created->value = $time;
    // NB this could generate pending emails.
    $transaction->save();
    //so it can be modified...
    return $transaction;
  }

  /**
   * Get two random wallets which were created before a given date.
   *
   * @note The below functions shouldn't really access the the db directly but
   * these are both unique function and also don't belong in the wallet storage
   * controller, in my opinion.
   */
  public function getWallets($time) {
    $wids = \Drupal::entityQuery('mcapi_wallet')
      ->condition('created', $time, '<')
      ->execute();
    if (count($wids) < 2) {
      throw new \Exception('Not enough wallets on date: ' . date('d M Y', $time));
    }
    shuffle($wids);

    return [reset($wids), next($wids)];
  }

  /**
   * Get the earliest time and an interval to generate transactions.
   *
   * @param int $count
   *   The number of transactions to create.
   *
   * @return array
   *   The unixtime of the first transaction, and the interval between
   *   subsequent transactions.
   */
  public function timing($since, $count) {
    $since += 1000;
    $period = REQUEST_TIME - $since;
    return $period / $count;
  }

  /**
   * Get the age of the second oldest wallet.
   * @return int
   *   The unixtime the first transaction time could take place.
   */
  private function firstTransactionTime() {
    return \Drupal::database()
      ->select('mcapi_wallet', 'w')
      ->fields('w', ['created'])
      ->orderBy('created', 'ASC')
      ->range(1, 2)->execute()->fetchField();
  }
}
