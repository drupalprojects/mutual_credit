<?php

namespace Drupal\mcapi_exchanges\Plugin\DevelGenerate;

use Drupal\mcapi\Entity\Currency;
use Drupal\ce_group_address\Plugin\DevelGenerate\NeighbourhoodsGenerate;
use Drupal\group\Entity\Group;
use Drupal\group\Plugin\DevelGenerate\GroupDevelGenerate;
use Drupal\address\Repository\CountryRepository;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generate a trading exchange and populate with users and transactions.
 *
 * @DevelGenerate(
 *   id = "exchange",
 *   label = @Translation("Exchanges"),
 *   description = @Translation("Generate up to 200 Countries"),
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
  protected $countries;

  static protected $exchanges = [
    'Mercury' => 'Mercurial Silver',
    'Venus' => 'Venutian Blinds',
    'Earth' => '$US Dollars',
    'Pluto' => 'Plutonic Pesos',
    'Mars' => 'Martian Moolah',
    'Saturn' => 'Saturnine Shillings',
    'Jupiter' => 'Jovial Jewels',
    'Neptune' => 'Nuptual candles',
    'Uranus' => 'Urinal blocks',
  ];


  public function __construct($configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, LanguageManagerInterface $language_manager, UrlGeneratorInterface $url_generator, DateFormatterInterface $date_formatter, $devel_generator, $group_membership_loader, $logger_factory, CountryRepository $country_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $module_handler, $language_manager, $url_generator, $date_formatter);
    $this->develGenerator = $devel_generator;
    $this->groupMembershipLoader = $group_membership_loader;
    $this->logger = $logger_factory->get('devel_generate');
    $this->countries = $country_manager->getList();
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
      $container->get('logger.factory'),
      $container->get('country_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['kill'] = [
      '#type' => 'hidden',
      '#value' => TRUE
    ];

    $form['num'] = [
      '#title' => $this->t('Number of exchanges'),
      '#description' => $this->t('Will be named after random countries'),
      '#type' => 'number',
      '#max' => count($this->countries),
      '#min' => 0,
      '#default_value' => 9,
      '#weight' => -2
    ];
    $form['planets'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Names of planets?'),
      '#default_value' => TRUE,
      '#weight' => $form['num']['#weight']+1,
      '#states' => [
        'visible' => [
          ':input[name="num"]' => ['value' => 9]
        ]
      ]
    ];

    $languages = $this->languageManager->getLanguages(LanguageInterface::STATE_ALL);
    foreach ($languages as $langcode => $language) {
      $options[$langcode] = $language->getName();
    }
    $form['add_language'] = [
      '#type' => 'select',
      '#title' => $this->t('Set language on groups'),
      '#multiple' => TRUE,
      '#description' => $this->t('Requires locale.module'),
      '#options' => $options,
      '#default_value' => [
        $this->languageManager->getDefaultLanguage()->getId(),
      ],
    ];
    return $form;
  }


  /**
   * {@inheritdoc}
   */
  protected function generateElements(array $values) {
    $this->currencies = Currency::loadMultiple();
    if ($values['kill']) {
      $this->groupsKill($values);
    }
    if ($values['num'] <= 50) {
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
    for ($i = 0; $i < $values['num']; $i++) {
      $exchange = $this->addExchange($values);
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
    $values['group_types'][] = 'exchange';
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

    $country_code = array_rand($this->countries);
    if ($values['planets'] && $values['num'] == 9) {
      list($planet, $currency_name) = each(static::$exchanges);
      $values['label'] = $planet;
      $values['currency_name'] = $currency_name;
    }
    else {
      $values['label'] = $this->countries[$country_code];
    }

    $values += [
      'label' => $this->countries[array_rand($this->countries)],
      'currency_name' => $this->t('@country coins', ['@country' => $values['label']]),
      //'label_length' => 2,
      //'role' => 'authenticated',
      'time_range' => mt_rand(0, strtotime('-2 years'))
    ];

    if (isset($results['add_language'])) {
      $langcodes = $results['add_language'];
      $langcode = $langcodes[array_rand($langcodes)];
    }
    else {
      $langcode = $this->languageManager->getDefaultLanguage()->getId();
    }

    $exchange = Group::create([
      'type' => 'exchange',
      'langcode' => $langcode,
      'uid' => 1, //exchanges are always created by user 1
      'created' => REQUEST_TIME - $values['time_range'],
    ]);
    // Populate all fields with sample values.
    $this->populateFields($exchange);

    $values['address'] = [
      'country_code' => $country_code,
      'dependent_locality' => NeighbourhoodsGenerate::randomName()
    ];

    //override the sample data with any given field values
    foreach ($values as $key => $val) {
      if ($exchange->hasField($key)) {
        $exchange->set($key, $val);
      }
    }
    $exchange->currencies->entity = $this->prepareCurrency($values['currency_name']);
    //this will create the default exchange.
    $exchange->save();
  }

  protected function prepareCurrency($name) {
    $id = strtolower(substr($name, 0, 2));
    $currency = Currency::load($id);
    if (!$currency) {
      $props = [
        'id' => $id,
        'name' => $name,
        'zero' => rand(0, 1),
         // Same for all.
        'issuance' => Currency::TYPE_PROMISE,
        'format' => $this->randCurrencyFormat($id),
        'uid' => 1,
      ];
      $currency = Currency::create($props);
      $currency->save();
    }
    return $currency;
  }


  protected function lastGroup($type = '') {
    $query = \Drupal::entityQuery('group');
    if ($type) {
      $query->condition('type', $type);
    }
    $query->range(0, 1)->sort('id', 'DESC');
    $exids = $query->execute();
    return Group::load(reset($exids));
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

