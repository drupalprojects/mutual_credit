<?php

namespace Drupal\mcapi_exchanges\Plugin\DevelGenerate;

use Drupal\mcapi\Entity\Currency;
use Drupal\mcapi_exchanges\Exchanges;
use Drupal\group\Entity\Group;
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

  protected $develGenerator;

  public function __construct(array $configuration, $plugin_id, array $plugin_definition, $entity_type_manager, $devel_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager);
    $this->develGenerator = $devel_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.develgenerate')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['#title'] = "(Re)-Generate 9 planetary exchanges";
    $form['kill'] = [
      '#type' => 'hidden',
      '#value' => TRUE
    ];
    // Memberships MUST be added if transactions are to be possible
    $form['exchange']['group_membership']['#disabled'] = TRUE;
    $form['exchange']['group_membership']['#default_value'] = TRUE;
    unset($form['exchange']['#options']['transactions']);

    $form['num'] = [
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
    ];
    $form['type'] = [
      '#type' => 'hidden',
      '#value' => 'exchange'
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
   *   kill, num, av_users, av_transactions
   *
   * @throws \Exception
   */
  private function generateContent($values) {
    $this->currencies = Currency::loadMultiple();
    $values['type'] = 'exchange';
    $this->contentKill($values);
    $func = $values['num'] == 9 ? 'get9names' : 'get200names';
    for ($i = 0; $i < $values['num']; $i++) {
      $nameval = $this->$func($i);
      list($values['exchange_name'], $values['currency_name']) = each($nameval);
      $exchange = $this->develGenerateExchangeAdd($values);
    }
  }

  /**
   * Method responsible for creating content when
   * the number of elements is greater than 50.
   */
  protected function generateBatchContent($values) {
    $this->currencies = Currency::loadMultiple();
    // Add the kill operation.
    $operations[] = ['devel_generate_operation', [$this, 'contentKill', $values]];

    // Add the operations to create the groups.
    $func = $values['num'] == 9 ? 'get9names' : 'get200names';

    for ($num = 0; $num < $values['num']; $num++) {
      $nameval = $this->$func($num);
      list($values['exchange_name'], $values['currency_name']) = each($nameval);
      $operations[] = ['devel_generate_operation', [$this,'batchContentAddExchange', $values]];
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
   * {@inheritdoc}
   */
  public function validateDrushParams($args) {
    $values['kill'] = drush_get_option('kill');
    $values['num'] = array_shift($args);
    return $values;
  }

  /**
   * Deletes all groups of the given type .
   *
   * @param array $values
   *   The input values from the settings form.
   */
  public function contentKill($values) {
    parent::contentKill($values);
    module_load_include('drush.inc', 'mcapi');
    foreach (Currency::loadMultiple() as $currency) {
      drush_mcapi_wipeslate($currency->id());
    }
  }

   /**
   *
   * @param array $values
   *   The results of the form submission.
   * @param array $context
   *   Batch context, includes array sandbox, array results, finished & message.
   */
  public function batchContentAddExchange($values, &$context) {
    $this->develGenerateExchangeAdd($values);
    $context['results']['num']++;
  }

  /**
   * Create one exchange with one or more currencies.
   */
  protected function develGenerateExchangeAdd(&$values) {
    // Allow any user to be a group owner.
    $values['role'] = 'authenticated';
    // Force the group generator to populate the group.
    $values['exchange']['group_membership'] = 'group_membership';
    $currencies = $cids = [];

    $id = strtolower(substr($values['currency_name'], 0, 2));
    $currency = Currency::load($id);
    if (!$currency) {
      $props = [
        'id' => $id,
        'name' => $values['currency_name'],
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

    parent::develGenerateGroupAdd($values);
    // Recover the exchange for some final alterations.
    $exids = \Drupal::entityQuery('group')->range(0, 1)->sort('id', 'DESC')->execute();
    $exchange = Group::load(reset($exids));
    $exchange->currencies->setValue($currency);
    $exchange->created->value = strtotime('-2 years');
    $exchange->label->value = $values['exchange_name'];
    $exchange->save();

    $this->generateTransactions($exchange, $values['av_transactions']);
  }

  /**
   * Create some transactions using wallets in a group and relate them to the
   * group.
   *
   * @param \Drupal\group\Entity\Group $group
   * @param int $num
   *
   * @todo Vary the number of transactions
   */
  private function generateTransactions(Group $group, $num) {
    //Make a transaction using any two wallets in the group.
    $rand = floor($num/4) + rand(0, ceil($num*1.5));
    $wids = Exchanges::walletsInExchange([$group->id()]);
    if (count($wids) < 2) {
      throw new \Exception('Not enough wallets in group '.$group->label());
    }
    $args = [
      'kill' => FALSE,
      'num' => $rand ,
      'type' => 'default',
      'conditions' => ['wid' => $wids],
      'curr_id' => $group->currencies->getValue()[0]['target_id']
    ];
    $this->develGenerator->createInstance('mcapi_transaction')
    ->generateElements($args);

    // These new transactions aren't returned, so we have to identify them by
    // getting the latest $rand transactions
    $xids = \Drupal::entityQuery('mcapi_transaction')
      ->sort('serial', 'desc')
      ->range(0, $rand)->execute();
    foreach ($xids as $xid) {
      $props = [
        'gid' => $group->id(),
        'type' => 'exchange-transactions',//should be exchange-group_membership
        'entity_id' => $xid,
      ];
      GroupContent::create($props)->save();
    }
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

}
