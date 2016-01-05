<?php

/**
 * @file
 * Contains \Drupal\mcapi_exchanges\Plugin\DevelGenerate\ExchangeGenerate.
 * @see https://www.drupal.org/node/2503429 for possible reason why batching doesn't work
 */

namespace Drupal\mcapi_exchanges\Plugin\DevelGenerate;

use Drupal\mcapi\Entity\Currency;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\devel_generate\DevelGenerateBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a DevelGenerate plugin.
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
 */
class ExchangeGenerate extends DevelGenerateBase implements ContainerFactoryPluginInterface {

  protected $entityTypeManager;
  
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
        200 => $this->t('One per country')
      ],
      '200' => [
        '#disabled' => TRUE//until devel generate is working properly with batches @todo
      ]
    );
    $form['shared'] = [
      '#title' => $this->t('percentage using existing currencies'),
      '#type' => 'number',
      '#min' => 0,
      '#max' => 100,
      '#default_value' => 80
    ];
    $form['unique'] = [
      '#title' => $this->t('percentage using unique currencies'),
      '#type' => 'number',
      '#min' => 0,
      '#max' => 100,
      '#default_value' => 80
    ];
    $form['closed'] = [
      '#title' => $this->t('percentage which are closed'),
      '#type' => 'number',
      '#min' => 0,
      '#max' => 100,
      '#default_value' => 20
    ];
    $form['deactivated'] = [
      '#title' => $this->t('percentage which are disabled'),
      '#type' => 'number',
      '#min' => 0,
      '#max' => 100,
      '#default_value' => 20
    ];
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function generateElements(array $values) {
    $this->currencies = $this->entityTypeManager->getStorage('mcapi_currency')->loadMultiple();
    $this->generateContent($values);
    //@todo when the batching is fixed, use batches every time.
    //$this->generateBatchContent($values);
  }
  
  /**
   * Method responsible for creating a small number of exchanges
   * 
   * @param type $values
   *   kill, num, since
   * @throws \Exception
   */
  private function generateContent($values) {
    
    if (!empty($values['kill'])) {
      $this->contentKill($values);
    }
    
    $uids = $this->entityTypeManager->getStorage('user')->getQuery()->execute();
    $chunk_size = ceil(count($uids)/$values['num']);
    foreach (array_chunk($uids, $chunk_size) as $delta => $uid_chunk) {
      if ($values['num'] == 9) {
        $nameval = $this->get9names($delta);
        list($name, $currency_name) = each($nameval);
      }
      else {
        list($name, $currency_name) = $this->get200names($delta);
      }
      $this->develGenerateExchangeAdd($values, $uid_chunk, $name, $currency_name);
    }
    if (function_exists('drush_log') && $i % drush_get_option('feedback', 1000) == 0) {
      drush_log(dt('Finished creating @count exchanges', ['@count' => drush_get_option('feedback', 1000)], 'ok'));
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
      $operations[] = array('devel_generate_operation', array($this, 'batchContentKill', $values));
    }

    // Add the operations to create the exchanges.
    for ($num = 0; $num < $values['num']; $num ++) {
      $operations[] = array('devel_generate_operation', array($this, 'batchContentAddExchange', $values));
    }

    // Start the batch.
    $batch = array(
      'title' => $this->t('Generating Small ads'),
      'operations' => $operations,
      'finished' => 'devel_generate_batch_finished',
      'file' => drupal_get_path('module', 'devel_generate') . '/devel_generate.batch.inc',
      'results' => [
        'num' => 0
      ]
    );
    batch_set($batch);
  }

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
    $exchanges = \Drupal\mcapi_exchanges\Entity\Exchange::loadMultiple();
    if (!empty($exchanges)) {
      $this->entityTypeManager->getStorage('mcapi_exchange')->delete($exchanges);
    }
  }

  /**
   * Create one exchange. Used by both batch and non-batch code branches.
   */
  protected function develGenerateExchangeAdd(&$results, $uids, $exchange_name, $currency_name) {
    //put the members in and choose a random member as exchange owner
    $props = [
      'name' => $exchange_name,
      'body' => $this->getRandom()->paragraphs(2),
      'status' => rand(0, 99) > $results['deactivated'],
      'code' => strtolower(str_replace(' ', '', $exchange_name)),
      'open' =>  rand(0, 99) > $results['closed'],
      'visibility' => rand(0, 2),
      'mail' => 'exchangeX@example.com',
      'uid' => $uids[array_rand($uids)]
    ];

    $exchange = \Drupal\mcapi_exchanges\Entity\Exchange::create($props);
    
    // Populate all fields with sample values.
    $this->populateFields($exchange);

    $currencies = $cids = [];
    if (rand(0, 99) < $results['shared']){
      $id = array_rand($this->currencies);
      $currencies[] = $this->currencies[$id];
      $cids[] = $id;
    }
    if (rand(0, 99) < $results['unique']) {
      $id = strtolower(substr($currency_name, 0, 2));
      $currency = Currency::load($id);
      if (!$currency) {
        $first = substr($currency_name, 0, 1);
        
        $currency = Currency::create([
          'id' => $id,
          'name' => $currency_name,
          'zero' => rand(0, 1),
          'issuance' => Currency::TYPE_EXCHANGE,//same for all
          'format' => $this->getFormat($id),
          'uid' => 1
        ]);
        
        $currency->save();
      }
      $currencies[] = $currency;
      $cids[] = $id;
    }
    $exchange->currencies->setValue($currencies);
    $exchange->save();
    drupal_set_message("Created exchange ".$exchange->label(). ' with currencies '.implode(', ', $cids));
    
    foreach ($this->entityTypeManager->getStorage('user')->loadMultiple($uids) as $user) {
      if ($user->id() < 2) continue;
      $user->set(EXCHANGE_OG_FIELD, $exchange->id())->save();
    }
    drupal_set_message('users '.implode(', ', $uids).' put into exchange '.$exchange->label());
  }
  
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
      ['Uranus' => 'Urinal blocks']
    ];
    
    return $results[$delta];
  }
    
  private function getFormat($id) {
    $formats = [
      ['000', ':', '99'],
      ['00', ':', '59', ':', '59'],
      ['00', ':', 59],
      ['00', ':', 59],
      ['00', ':', 59],
      ['000'],
      ['000']
    ];
    $rand = $formats[array_rand($formats)];
    array_unshift($rand, '<b>'.strtoupper($id).'</b>');
    return $rand;
  }
  
}
