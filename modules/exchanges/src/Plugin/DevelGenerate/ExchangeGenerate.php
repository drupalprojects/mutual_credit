<?php

namespace Drupal\mcapi_exchanges\Plugin\DevelGenerate;

use Drupal\mcapi\Entity\Currency;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Plugin\DevelGenerate\GroupGenerate;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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
class ExchangeGenerate extends GroupGenerate implements ContainerFactoryPluginInterface {

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
    $form['av_transactions'] = [
      '#title' => $this->t('Average num of transactions per exchange'),
      '#descriptions' => $this->t('New transactions will be created between members of the same exchanges.'),
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
   * Method responsible for creating a small number of exchanges.
   *
   * @param string or int or object... $values
   *   kill, num, shared, unique, av_users, av_transactions
   *
   * @throws \Exception
   */
  private function generateContent($values) {
    if (!empty($values['kill'])) {
      $this->contentKill($values);
    }

    $this->currencies = Currency::loadMultiple();
    $func = $values['num'] == 9 ? 'get9names' : 'get200names';
    for ($i = 0; $i < $values['num']; $i++) {
      $nameval = $this->$func($i);
      list($name, $currency_name) = each($nameval);
      $exchange = $this->develGenerateExchangeAdd($values, $name, $currency_name);
    }
  }

  /**
   * Method responsible for creating content when
   * the number of elements is greater than 50.
   */
//  private function generateBatchContent($values) {
//    // Add the kill operation.
//    if ($values['kill']) {
//      $operations[] = ['devel_generate_operation', [$this, 'batchContentKill', $values]];
//    }
//
//    // Add the operations to create the exchanges.
//    for ($num = 0; $num < $values['num']; $num++) {
//      $operations[] = ['devel_generate_operation', [$this, 'batchContentAddExchange', $values]];
//    }
//
//    // Start the batch.
//    $batch = [
//      'title' => $this->t('Generating Exchanges'),
//      'operations' => $operations,
//      'finished' => 'devel_generate_batch_finished',
//      'file' => drupal_get_path('module', 'devel_generate') . '/devel_generate.batch.inc',
//      'results' => [
//        'num' => 0,
//      ],
//    ];
//    batch_set($batch);
//  }

  /**
   *
   */
//  public function batchContentAddExchange($vars, &$context) {
//    $this->develGenerateExchangeAdd($context['results']);
//    $context['results']['num']++;
//  }

  /**
   * {@inheritdoc}
   */
  public function validateDrushParams($args) {
    $values['kill'] = drush_get_option('kill');
    $values['num'] = array_shift($args);
    return $values;
  }

  /**
   * Create one exchange with one or more currencies.
   */
  protected function develGenerateExchangeAdd(&$values, $exchange_name, $currency_name) {
    $values['type'] = 'exchange';
    $values['role'] = 'authenticated';
    $exchange = parent::develGenerateGroupAdd($values);

    $exchange->created->value = strtotime('-2 years');
    $exchange->label->value = $exchange_name;

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

    // @todo vary the number of transactions
    $this->generateTransactions($group, $values['av_transactions']);

    return $exchange;
  }

  /**
   * Get names and currency names for each of 9 exchanges.
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

  /**
   * Get a currency format at random
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

  /**
   * Create some transactions using wallets in a group and relate them to the
   * group.
   *
   * @param \Drupal\mcapi_exchanges\Plugin\DevelGenerate\Group $group
   * @param int $num
   */
  private function generateTransactions(Group $group, $num) {

    
  }

}
