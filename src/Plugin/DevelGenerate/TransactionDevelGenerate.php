<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\DevelGenerate\TransactionDevelGenerate.
 * @see https://www.drupal.org/node/2503429 for possible reason why batching doesn't work
 */

namespace Drupal\mcapi\Plugin\DevelGenerate;

use Drupal\mcapi\Mcapi;
use Drupal\mcapi\Entity\Transaction;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
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
 *   description = @Translation("Generate a given number of transactions.."),
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
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;
  /**
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param array $plugin_definition
   * @param \Drupal\Core\Entity\EntityStorageInterface $transaction_storage
   *   The transaction storage.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityStorageInterface $transaction_storage, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
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
      $container->get('module_handler'),
      $container->get('date.formatter')
    );
  }

  /**
   * find some suitable wallets
   * @staticvar type $wallet_ids
   * @return integer[] or FALSE
   *   wallet ids, shuffled
   */
  public function prepareWallets() {
    static $wallet_ids;
    if (!isset($wallet_ids)) {
      $wallet_ids = \Drupal::entityTypeManager()
        ->getStorage('mcapi_wallet')->getQuery()
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
   * Method responsible for creating a small number of transactions
   *
   * @param type $values
   *   kill, num
   * @throws \Exception
   */
  private function generateContent($values) {
    if (!empty($values['kill'])) {
      $this->contentKill($values);
    }
    $wids = $this->prepareWallets();
    for ($i = 1; $i <= $values['num']; $i++) {
      shuffle($wids);
      $this->develGenerateTransactionAdd($results, reset($wids), end($wids));
    }
    if (function_exists('drush_log') && $i % drush_get_option('feedback', 1000) == 0) {
      drush_log(dt('Completed @feedback transactions ', ['@feedback' => drush_get_option('feedback', 1000)], 'ok'));
    }

    $this->setMessage($this->formatPlural($values['num'], '1 transaction created.', 'Finished creating @count transactions'));
  }

  private function getSince() {
    static $since;
    if (!$since) {
      $since = \Drupal::database()
        ->select('mcapi_wallet', 'w')
        ->fields('w', ['created'])
        ->orderBy('created', 'DESC')
        ->range(0, 1)
        ->execute()->fetchField();
    }
    return $since;
  }

  /**
   * Method responsible for creating content when
   * the number of elements is greater than 50.
   */
  private function generateBatchContent($values) {
    // Add the kill operation.
    if ($values['kill']) {
      $operations[] = array('devel_generate_operation', array($this, 'batchContentKill', $values));
    }


    // Add the operations to create the transactions.
    for ($num = 0; $num < $values['num']; $num ++) {
      $operations[] = array('devel_generate_operation', array($this, 'batchContentAddTransaction', $values));
    }

    // Start the batch.
    $batch = array(
      'title' => $this->t('Generating Content'),
      'operations' => $operations,
      'finished' => 'devel_generate_batch_finished',
      'file' => drupal_get_path('module', 'devel_generate') . '/devel_generate.batch.inc',
    );
    batch_set($batch);
  }

  public function batchContentAddTransaction($vars, &$context) {
    $wids = $this->prepareWallets();
    $this->develGenerateTransactionAdd($context['results'], reset($wids), end($wids));
    $context['results']['num']++;
  }

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
   */
  protected function develGenerateTransactionAdd(&$results, $wid1, $wid2) {
    $props = array(
      'payer' => $wid1,
      'payee' => $wid2,
      'type' => $this->getSetting('type') ? : 'default',
      'creator' => 1,
      'description' => $this->getRandom()->sentences(1)
    );
    $transaction = \Drupal\mcapi\Entity\Transaction::create($props);
    // Populate all fields with sample values.
    $this->populateFields($transaction);
    //change the created time of the transactions, coz they mustn't be all in the same second
    $transaction->created->value = rand($this->getSince(), REQUEST_TIME);
    $transaction->save();

    if (isset($transaction->signatures)) {
      //signatures already exist because they were created in the presave phase
      foreach ($transaction->signatures as $uid => $signed) {
        //leave 1 in 10 signatures unsighed.
        if (rand(0, 9) > 0) {
          \Drupal\mcapi_signatures\Signatures::sign(
            $transaction,
            \Drupal\user\Entity\User::load($uid)
          );
        }
      }
      $transaction->save();
    }


      $transaction->save();//testing only

  }

}
