<?php

namespace Drupal\mcapi_exchanges\Plugin\DevelGenerate;

use Drupal\mcapi\Entity\Currency;
use Drupal\group\Entity\Group;
use Drupal\group\Plugin\DevelGenerate\GroupDevelGenerate;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;

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
class ExchangeGenerate extends GroupDevelGenerate implements ContainerFactoryPluginInterface {

  protected $develGenerator;
  protected $groupMembershipLoader;
  protected $logger;

  public function __construct($configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, LanguageManagerInterface $language_manager, UrlGeneratorInterface $url_generator, DateFormatterInterface $date_formatter, $devel_generator, $group_membership_loader, $logger_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $module_handler, $language_manager, $url_generator, $date_formatter);
    $this->develGenerator = $devel_generator;
    $this->groupMembershipLoader = $group_membership_loader;
    $this->logger = $logger_factory->get('devel_generate');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('language_manager'),
      $container->get('url_generator'),
      $container->get('date.formatter'),
      $container->get('plugin.manager.develgenerate'),
      $container->get('group.membership_loader'),
      $container->get('logger.factory')
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
      '#title' => $this->t('Generate how many exchanges?'),
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
    $form['group_types'] = [
      '#type' => 'hidden',
      '#value' => ['exchange']
    ];
    return $form;
  }


  /**
   * {@inheritdoc}
   */
  protected function generateElements(array $values) {
    $this->currencies = Currency::loadMultiple();
    $this->groupsKill($values);

    if ($values['num'] <= 10) {
      $this->generateContent($values);
    }
    else {
      $this->generateBatchContent($values);
    }
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
    $this->preGroup($values);
    if ($values['num'] == 9) {
      for ($i = 0; $i < 9; $i++) {
        $nameval = $this->get9names($i);
        list($values['exchange_name'], $values['currency_name']) = each($nameval);
        $exchange = $this->addExchange($values);
      }
    }
    else {
      for ($i = 0; $i < $values['num']; $i++) {
        $exchange = $this->addExchange($values);
      }
    }
  }

  /**
   * Method responsible for creating content when
   * the number of elements is greater than 50.
   */
  protected function generateBatchContent($values) {
    $this->currencies = Currency::loadMultiple();
    $batch = [
      'title' => $this->t('Generating Exchanges'),
      'finished' => 'devel_generate_batch_finished',
      'file' => drupal_get_path('module', 'devel_generate') . '/devel_generate.batch.inc',
      'results' => [
        'num' => 0,
      ],
    ];
    // Add the kill operation.
    $batch['operations'][] = ['devel_generate_operation', [$this, 'groupsKill', $values]];

    for ($num = 0; $num < $values['num']; $num++) {
      $batch['operations'][] = ['devel_generate_operation', [$this,'batchContentAddExchange', $values]];
    }
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
  public function groupsKill($values) {
    parent::groupsKill($values);
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
    $this->addExchange($values);
    $context['results']['num']++;
  }

  /**
   * Create one exchange with one or more currencies.
   */
  protected function addExchange($values) {
    // Allow any user to be a group owner.
    // Force the group generator to populate the group.
    $currencies = $cids = [];
    if (!isset($values['currency_name'])) {
      $random = new \Drupal\Component\Utility\Random();
      $values['currency_name'] = $values['exchange_name'] = $random->word(mt_rand(1, 12));
    }

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

    $values['label_length'] = 2;
    $values['group_types'] = ['exchange'];
    $values['role'] = 'authenticated';

    parent::addGroup($values);
    // Recover the exchange for some final alterations.
    $exchange = $this->lastExchange();

    $exchange->currencies->setValue($currency);
    $exchange->created->value = strtotime('-2 years');
    $exchange->label->value = $values['exchange_name'];
    $exchange->save();
    
    // Grant the admin role to the exchange owner
    $this->groupMembershipLoader
      ->load($exchange, $exchange->getOwner())
      ->getGroupContent()
      ->set('group_roles', ['exchange-admin'])
      ->save();
    $this->logger->notice('Granted exchange-admin role to user '.$exchange->getOwnerId().', owner of exchange '.$exchange->id());
  }



  protected function lastExchange() {
    $exids = \Drupal::entityQuery('group')->range(0, 1)->sort('id', 'DESC')->execute();
    return Group::load(reset($exids));
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
