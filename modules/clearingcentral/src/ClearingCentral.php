<?php

namespace Drupal\mcapi_cc;

use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\mcapi\Entity\Wallet;
use Drupal\mcapi\Plugin\Validation\Constraint\CanPayIn;
use Drupal\mcapi\Plugin\Validation\Constraint\CanPayOut;
use Drupal\mcapi\Entity\Currency;
use Drupal\mcapi\Entity\Transaction;
use Drupal\user\Entity\User;
use GuzzleHttp\Exception\RequestException;

/**
 * Manage communications with clearing central web service
 */
class ClearingCentral implements IntertradingInterface {

  protected $allSettings;
  protected $nameLookup;
  protected $httpClient;
  protected $logger;
  protected $country;
  protected $accountSwitcher;

  private $exchangeId;
  private $password;
  private $currId;


  const CLEARING_CENTRAL_URL = 'http://cxn.org.za';
  const CLEARING_CENTRAL_IP = '69.61.35.151';

  /**
   *
   * @param Drupal\Core\Config\ConfigFactory $config_factory
   * @param GuzzleHttp $http_client
   * @param Drupal\Core\KeyValueStore\KeyValueFactory $key_value
   * @param Drupal\Core\Entity\Query $entity_query
   * @param Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   * @param Drupal\Core\Session\AccountSwitcher $account_switcher
   *
   * @see mcapi_cc.services.yml
   */
  function __construct($config_factory, $http_client, $key_value, $entity_query, $logger_factory, $account_switcher) {
    $this->siteConfig = $config_factory->get('system.site');
    $this->httpClient  = $http_client;
    $this->allSettings = $key_value->get('clearingcentral');
    $this->nameLookup  = $key_value->get('exchangeNames');
    $this->walletEntityQuery = $entity_query;
    $this->logger = $logger_factory->get('Clearing Central');
    $this->country = $config_factory->get('system.date')->get('country.default');
    $this->accountSwitcher = $account_switcher;
  }

  /**
   * {@inheritdoc}
   */
  public function init($curr_id, $login, $pass) {
    $this->exchangeId = $login;
    $this->pass = $pass;
    $this->currId = $curr_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function send(TransactionInterface $transaction, $remote_exchange_id, $remote_user_id) {
    $remote_transaction = $this->prepareSend($transaction, $remote_exchange_id, $remote_user_id);
    $this->logger('Clearing Central')->debug('<pre>'. print_r($remote_transaction, 1) .'</pre>');
    try {
      $url = self::CLEARING_CENTRAL_URL.'/txinput.php?'.http_build_query($remote_transaction);
      $this->logger('Clearing Central')->debug($url);
      $result = $this->httpClient->post($url);
    }
    catch (RequestException $e) {
      drupal_set_message($e->getMessage());
      return [];
    }

    $params = [];

    if ($result->getStatusCode() == 200) {
      // These are needed in hook_mcapi_transaction_insert.
      $transaction->remote_exchange_id = $remote_exchange_id;
      $transaction->remote_user_id = $remote_user_id;
      parse_str($result->getBody()->getContents(), $params);
    }
    else {
      $message = t('Clearing central failed to respond.');
      $this->logger('clearingcentral')->error($message);
      drupal_set_message($message, 'error');
      $form_state->setRebuild(TRUE);
    }
    return $params;
  }


  /**
   * {@inheritdoc}
   */
  public function receive(&$params) {
    $props = [
      'uuid' => $params['txid'],
      'type' => 'remote',
      'description' => $params['description']
    ];

    if ($params['outgoing']) {
      //payment is going from the originating exchange to this exchange
      $local_exchange_id = $params['seller_nid'];
      $other_exchange_id = $params['buyer_nid'];
      $other_exchange_name = $params['buyer_xname'];
      $other_user_name = $params['buyer_name'];
      $wid = $this->walletIdFromfragment($params['seller_id']);
    }
    else {
      //payment is going from this exchange to the originating exchange
      $local_exchange_id = $params['buyer_nid'];
      $other_exchange_id = $params['seller_nid'];
      $other_exchange_name = $params['seller_xname'];
      $other_user_name = $params['seller_name'];
      $wid = $this->walletIdFromfragment($params['buyer_id']);
    }
    if (!$wid) {
      $params['response'] = 0;
      return;
    }

    //store this for when we need to view the transaction
    $this->nameLookup->set($other_exchange_id, $other_exchange_name);

    $currency = Currency::load(clearingCentral($intertrading_wid)->getCurrencyId());

    list($parts[1], $parts[3]) = explode('.', number_format($params['amount'], 2));
    $props['worth'] = [
      [
        'curr_id' => $currency->id(),
        'value' => $currency->unformat($parts)
      ]
    ];
    $props['creator'] = $intertrading_wid;

    $this->accountSwitcher->switchTo(User::load(1));

    if ($params['outgoing']) {
      //props to generate a transaction
      $props['payer'] = $intertrading_wid;
      $props['payee'] = $wid;
      //prepare the params to send back
      $holder = Wallet::load($props['payee'])->getHolder();
      $params['seller_name'] = $holder->label();
      $params['seller_email'] = $holder->getEmail();
      $params['seller_xname'] = $this->siteConfig->get('name');
      $params['seller_country'] = $this->country;
    }
    else {
      //props to generate a transaction
      $props['payee'] = $intertrading_wid;
      $props['payer'] = $wid;
      //prepare the params to send back
      $holder = Wallet::load($props['payer'])->getHolder();
      $params['buyer_name'] = $holder->label();
      $params['buyer_email'] = $holder->getEmail();
      $params['buyer_xname'] = $this->siteConfig->get('name');
      $params['buyer_country'] = $this->country;
    }
    $transaction = Transaction::create($props);
    $transaction->remote_exchange_id = $other_exchange_id;
    $transaction->remote_user_id = $other_exchange_name;
    $transaction->remote_user_name = $other_user_name;


    $violations = $transaction->validate();
    if (count($violations)) {
      foreach ($violations as $violation) {
        //skip some constraints
        if ($violation->getPropertyPath() == 'payee' and $violation->getConstraint() instanceOf CanPayIn) {
          continue;
        }
        elseif($violation->getPropertyPath() == 'payer' and $violation->getConstraint() instanceOf CanPayOut) {
          continue;
        }
        $this->logger->error($violation->getMessage());
        $bad = TRUE;
      }
    }
    if (isset($bad)) {
      $params['response'] = 5;
    }
    else {
      $params['response'] = CEN_SUCCESS;
      $transaction->save();
    }
    $this->accountSwitcher->switchBack();
  }

  /**
   * {@inheritdoc}
   */
  public static function responseLookup($code) {
    $codes = [
      0 => t('Unknown error in Clearing Central'),
      CEN_SUCCESS => 'success',
      2 => t('Buyer does not exist (unknown account number)'),
      3 => t('Exchange not registered on Clearing Central'),
      4 => t('Transaction denied ("no funds", over limit, account locked, exchange over deficit limit etc.)'),
      5 => t('faulty data'),
      6 => t('Repeat transaction and so rejected by CC (same TXID submitted)'),
      7 => t('URL error'),
      8 => t('Conversion rate not set'),
      9 => t('Server error (e.g. cannot access db)'),
      10 => t('Password is wrong in settings'),
      11 => t('IP of incoming server not in CC DB'),
      12 => t('No TXID provided (update/delete only)'),
      13 => t('TXID does not exist (update/delete only)'),
      14 => t('Unable to connect to remote server'),
      15 => t('Failed to connect to host or proxy')
    ];
    return $codes[$code];
  }



  /**
   * Convert a transaction entity to a Clearing central remote transaction array
   *
   * @param TransactionInterface $transaction
   *
   * @return array
   */
  private function prepareToSend(TransactionInterface $transaction, $outgoing, $remote_exchange_id, $remote_user_id) {
    $wid = \Drupal\mcapi\Exchange::intertradingWalletId();
    $currency = Currency::load($intertrading_settings['curr_id']);
    $CcTransaction = [
      'txid' => $transaction->uuid->value,
      'description' => $transaction->description->value,
      'outgoing' => $outgoing
    ];
    $payer = $transaction->payer->entity;
    $payee = $transaction->payee->entity;

    if ($outgoing) {
      $CcTransaction += [
        'buyer_nid' => $this->exchangeId,
        'password' => $this->pass,
        'buyer_id' => $payer->id(),
        'buyer_name' => $payer->label(),
        'seller_nid' => $remote_exchangeId,
        'seller_id' => $remote_user_id,
        'amount' => $transaction->amount
      ];
    }
    elseif ($payee->payways->value == Wallet::PAYWAY_AUTO) {
      $CcTransaction += [
        'seller_nid' => $this->exchange_id,
        'password' => $this->pass,
        'seller_id' => $payee->id(),
        'seller_name' => $payee->label(),
        'buyer_nid' => $remote_exchange_id,
        'buyer_id' => $remote_user_id,
        'amount' => $currency->format($transaction->worth->value, Currency::DISPLAY_PLAIN)
      ];
    }
    return $CcTransaction;
  }

  /**
   * Determine which wallet a fragment of text refers to.
   * @param type $fragment
   * @return int
   *   The wallet ID
   */
  private function walletIdfromFragment($fragment) {
    $wids = $this->walletEntityQuery
      ->condition('payways', Wallet::PAYWAY_AUTO, '<>')
      ->condition('name', '%'.$fragment.'%', 'LIKE')
      ->execute();
    if (count($wids) > 1) {
      $this->logger->error('Found more than one wallet matching fragment %fragment', ['%fragment' => $fragment]);
    }
    elseif (empty($wids)) {
      $this->logger->error('Found no wallets matching fragment %fragment', ['%fragment' => $fragment]);
    }
    return reset($wids);
  }


  /**
   * {@inheritdoc}
   */
  static function nidSearchUrl() {
    return Self::CLEARING_CENTRAL_URL . '/nidsearch.php';
  }

  /**
   * {@inheritdoc}
   */
  static function getCurrencyId() {
    return $this->currId;
  }
}