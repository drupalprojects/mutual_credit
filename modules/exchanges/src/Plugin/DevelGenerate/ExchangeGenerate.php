<?php

namespace Drupal\mcapi_exchanges\Plugin\DevelGenerate;

use Drupal\group\Entity\GroupContent;
use Drupal\mcapi\Entity\Currency;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\devel_generate\DevelGenerateBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generate a trading exchange and populate with users and transactions.
 *
 * @DevelGenerate(
 *   id = "exchange",
 *   label = @Translation("Exchanges"),
 *   description = @Translation("Generate up to 9 exchanges"),
 *   url = "exchange",
 *   permission = "administer devel_generate",
 *   settings = {
 *     "num" = 9,
 *     "kill" = TRUE
 *   }
 * )
 *
 */
class ExchangeGenerate extends DevelGenerateBase implements ContainerFactoryPluginInterface {

  protected $entityTypeManager;

  /**
   *
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['kill'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Clear all existing exchanges'),
      '#default_value' => $this->getSetting('kill'),
    );
    $form['num'] = array(
      '#type' => 'radios',
      '#title' => $this->t('How many exchanges would you like to generate?'),
      '#description' => $this->t('Each user will be randomly put into one exchange'),
      '#default_value' => $this->getSetting('num'),
      '#required' => TRUE,
      '#options' => [
        9 => $this->t('Nine planets'),
      ],
      '200' => [
      // Until devel generate is working properly with batches @todo.
        '#disabled' => TRUE,
      ],
    );
    $form['shared'] = [
      '#title' => $this->t('percentage using existing currencies'),
      '#type' => 'number',
      '#min' => 0,
      '#max' => 100,
      '#default_value' => 80,
    ];
    $form['unique'] = [
      '#title' => $this->t('percentage using unique currencies'),
      '#type' => 'number',
      '#min' => 0,
      '#max' => 100,
      '#default_value' => 80,
    ];
    $form['closed'] = [
      '#title' => $this->t('percentage which are closed'),
      '#type' => 'number',
      '#min' => 0,
      '#max' => 100,
      '#default_value' => 20,
    ];
    $form['deactivated'] = [
      '#title' => $this->t('percentage which are disabled'),
      '#type' => 'number',
      '#min' => 0,
      '#max' => 100,
      '#default_value' => 20,
    ];
    $form['av_users'] = [
      '#title' => $this->t('Average num of users'),
      '#type' => 'number',
      '#min' => 0,
      '#max' => 500,
      '#default_value' => 0,
    ];
    $form['av_transactions'] = [
      '#title' => $this->t('Average num of transactions per new user'),
      '#type' => 'number',
      '#min' => 0,
      '#max' => 100,
      '#default_value' => 0,
      '#states' => [
        'disabled' => [
          ':input[name="av_users"]' => ['value' => 0]
        ]
      ]
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function generateElements(array $values) {
    $this->currencies = $this->entityTypeManager->getStorage('mcapi_currency')->loadMultiple();
    $this->generateContent($values);
    // @todo when the batching is fixed, use batches every time.
    // $this->generateBatchContent($values);
  }

  /**
   * Method responsible for creating a small number of exchanges.
   *
   * @param string or int or object... $values
   *   kill, num, shared, unique, closed, deactivated, av_users, av_transactions
   *
   * @throws \Exception
   */
  private function generateContent($values) {
    if (!empty($values['kill'])) {
      $this->contentKill($values);
    }
    $func = $values['num'] == 9 ? 'get9names' : 'get200names';
    for ($i = 0; $i < $values['num']; $i++) {
      $nameval = $this->$func($i);
      list($name, $currency_name) = each($nameval);
      $exchange = $this->develGenerateExchangeAdd($values, $name, $currency_name);
    }

    $this->setMessage($this->formatPlural($values['num'], '1 exchange created.', 'Finished creating @count exchanges'));
  }

  /**
   * Method responsible for creating content when
   * the number of elements is greater than 50.
   */
  private function generateBatchContent($values) {
    // Add the kill operation.
    if ($values['kill']) {
      $operations[] = ['devel_generate_operation', [$this, 'batchContentKill', $values]];
    }

    // Add the operations to create the exchanges.
    for ($num = 0; $num < $values['num']; $num++) {
      $operations[] = ['devel_generate_operation', [$this, 'batchContentAddExchange', $values]];
    }

    // Start the batch.
    $batch = [
      'title' => $this->t('Generating Exchanges'),
      'operations' => $operations,
      'finished' => 'devel_generate_batch_finished',
      'file' => drupal_get_path('module', 'devel_generate') . '/devel_generate.batch.inc',
      'results' => [
        'num' => 0,
      ],
    ];
    batch_set($batch);
  }

  /**
   *
   */
  public function batchContentAddExchange($vars, &$context) {
    $this->develGenerateExchangeAdd($context['results']);
    $context['results']['num']++;
  }

  /**
   * {@inheritdoc}
   */
  public function validateDrushParams($args) {
    $values['kill'] = drush_get_option('kill');
    $values['num'] = array_shift($args);
    return $values;
  }

  /**
   * Deletes all exchanges .
   *
   * @param array $values
   *   The input values from the settings form.
   */
  protected function contentKill($values) {
    $exchanges = $this->entityTypeManager->getStorage('group')->loadByProperties(['type' => 'exchange']);
    unset($exchanges[1]);
    if (!empty($exchanges)) {
      $this->entityTypeManager->getStorage('group')->delete($exchanges);
    }
  }

  /**
   * Create one exchange. Used by both batch and non-batch code branches.
   */
  protected function develGenerateExchangeAdd(&$results, $exchange_name, $currency_name) {
    // Create an exchange. All exchanges will have been created 2 years ago
    $owner = $this->newMem();
    $props = [
      'label' => $exchange_name,
      'uid' => $owner->id(),
      'type' => 'exchange',
      'created' => strtotime('-2 years'),
    ];

    $exchange = \Drupal\group\Entity\Group::create($props);

    // Populate all fields with sample values.
    $this->populateFields($exchange);

    $currencies = $cids = [];
    if (rand(0, 99) < $results['shared']) {
      $id = array_rand($this->currencies);
      $currencies[] = $this->currencies[$id];
      $cids[] = $id;
    }
    if (rand(0, 99) < $results['unique']) {
      $id = strtolower(substr($currency_name, 0, 2));
      $currency = Currency::load($id);
      if (!$currency) {
        $props = [
          'id' => $id,
          'name' => $currency_name,
          'zero' => rand(0, 1),
           // Same for all.
          'issuance' => Currency::TYPE_PROMISE,
          'format' => $this->randCurrencyFormat($id),
          'uid' => 1,
        ];
        $currency = Currency::create($props);
        $currency->save();
      }
      $currencies[] = $currency;
      $cids[] = $id;
    }
    $exchange->currencies->setValue($currencies);
    $exchange->save();

    //randomise the number of members to add to this exchange
    $memcount = $this->randomise($results['av_users']);
    //$i = 1 because we already created the owner
    for ($i = 1; $i < $memcount; $i++ ) {
      // Borrowed from the user generator/
      $account = $this->newMem($exchange);
      // Now create some transactions for this exchange.
      if ($results['av_transactions']) {
        $transaction_generator = \Drupal::service('plugin.manager.develgenerate')->createInstance('mcapi_transaction');
        $oneYearAgo = strtotime('-1 year');
        $num_of_transactions = $this->randomise($results['av_transactions'] * $memcount);
        $interval = $transaction_generator->timing($oneYearAgo, $num_of_transactions);
        for ($j = 0; $j < $num_of_transactions; $j++) {
          $transaction = $transaction_generator->develGenerateTransactionAdd($values, $oneYearAgo + $interval * $j);
          GroupContent::create([
            'gid' => $exchange->id(),
            'type' => 'exchange-group_transactions',
            'entity_id' => $transaction->id(),
          ])->save();
        }
      }
    }
    return $exchange;
  }

  /**
   *
   */
  private function get9names($delta) {
    $results = [
      ['Mercury' => 'Mercurial Silver'],
      ['Venus' => 'Venutian Blinds'],
      ['Earth' => '$US Dollars'],
      ['Pluto' => 'Plutonic Pesos'],
      ['Mars' => 'Martian Moolah'],
      ['Saturn' => 'Saturnine Shillings'],
      ['Jupiter' => 'Jovial Jewels'],
      ['Neptune' => 'Nuptual candles'],
      ['Uranus' => 'Urinal blocks'],
    ];
    return $results[$delta];
  }

  private function newMem($exchange = NULL) {
    $name = $this->getRandom()->word(mt_rand(6, 12));
    $account = $this->entityTypeManager->getStorage('user')->create([
      'uid' => NULL,
      'name' => $name,
      'pass' => 'a',
      'mail' => $name . '@example.com',
      'status' => 1,
      'created' => REQUEST_TIME - rand(0, strtotime('-2 years')),
      // A flag to let hook_user_* know that this is a generated user.
      'devel_generate' => TRUE,
    ]);
    $this->populateFields($account);
    $account->save();
    if ($exchange) {
      // Create memberships
      GroupContent::create([
        'gid' => $exchange->id(),
        // $exchange->getGroupType()->getContentPlugin('group_membership')->getContentTypeConfigId()
        'type' => 'exchange-group_membership',
        'entity_id' => $account->id(),
      ])->save();
    }
    return $account;
  }

  /**
   *
   */
  private function randCurrencyFormat($id) {
    $formats = [
      ['000', ':', '99'],
      ['00', ':', '59', ':', '59'],
      ['00', ':', 59],
      ['00', ':', 59],
      ['00', ':', 59],
      ['000'],
      ['000'],
    ];
    $rand = $formats[array_rand($formats)];
    array_unshift($rand, strtoupper($id));
    return $rand;
  }

  private function randomise($num) {
    return rand($num/5, 9*$num/5);
  }

  private function makeTransaction($group) {

  }
}
