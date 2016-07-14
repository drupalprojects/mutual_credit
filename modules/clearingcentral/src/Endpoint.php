<?php

namespace Drupal\mcapi_cc;

use Drupal\Component\Utility\UrlHelper;
use Drupal\user\Entity\User;
use Drupal\mcapi\Entity\Wallet;
use Drupal\mcapi\Entity\Currency;
use Drupal\mcapi\Entity\Transaction;
use Drupal\system\Controller\SystemController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\mcapi\Plugin\Validation\Constraint\CanPayIn;
use Drupal\mcapi\Plugin\Validation\Constraint\CanPayOut;

/**
 * Returns responses for Wallet routes.
 */
class Endpoint extends SystemController {

  private $logger;
  private $siteConfig;
  private $accountSwitcher;
  private $walletEntityQuery;
  private $exchangeNames;
  private $country;
  private $intertradingSettings;

  /**
   * Constructs a new SystemController.
   */
  public function __construct($logger, $config_factory, $account_switcher, $entity_query, $key_value_store, $date) {
    $this->logger = $logger->get('Clearing Central');
    $this->siteConfig = $config_factory->get('system.site');
    $this->accountSwitcher = $account_switcher;
    $this->walletEntityQuery = $entity_query->get('mcapi_wallet');
    $this->exchangeNames = $key_value_store->get('exchangeNames');

    $this->country = $date->get('country.default');
    $this->intertradingSettings = $key_value_store->get('clearingcentral');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('account_switcher'),
      $container->get('entity.query'),
      $container->get('keyvalue'),
      $container->get('system.date')
    );
  }

  /**
   * Receive the message from clearing central.
   *
   * Also validate, save the transaction, return transaction or an error code.
   */
  public function receiver() {
    $post = filter_input_array(INPUT_POST);

    // Receive the transaction as server B.
    $this->makeTransaction($post);
    $response = new Response(NULL, $post['response'] == CEN_SUCCESS ? 201 : 200);

    $response->setContent(UrlHelper::buildQuery($post));
    $response->send();
    exit;
  }

  /**
   * Create a new transaction from the sent params.
   *
   * @param array $params
   *   Data from clearing central about the proposed transaction
   *   'outgoing' means whether the transaction was outgoing from its source.
   */
  public function makeTransaction(&$params) {

    $props = [
      'uuid' => $params['txid'],
      'type' => 'remote',
      'description' => $params['description'],
    ];

    if ($params['outgoing']) {
      // Payment is going from the originating exchange to this exchange.
      $local_exchange_id = $params['seller_nid'];
      $other_exchange_id = $params['buyer_nid'];
      $other_exchange_name = $params['buyer_xname'];
      $other_user_name = $params['buyer_name'];
    }
    else {
      // Payment is going from this exchange to the originating exchange.
      $local_exchange_id = $params['buyer_nid'];
      $other_exchange_id = $params['seller_nid'];
      $other_exchange_name = $params['seller_xname'];
      $other_user_name = $params['seller_name'];
    }
    // Store this for when we need to view the transaction.
    $this->exchangeNames->set($other_exchange_id, $other_exchange_name);
    $intertrading_wid = $this->getIntertadingWalletFromExchangeId($local_exchange_id);

    if (!$intertrading_wid) {
      $this->logger->error('Could not identify intertrading wallet connected to @id', ['@id' => $other_exchange_id]);
      // Send something back to the server.
      $params['response'] = 3;
      return;
    }

    $settings = $this->intertradingSettings->get($intertrading_wid);
    $currency = Currency::load($settings['curr_id']);

    list($parts[1], $parts[3]) = explode('.', number_format($params['amount'], 2));
    $props['worth'] = [
      [
        'curr_id' => $currency->id(),
        'value' => $currency->unformat($parts),
      ],
    ];
    $props['creator'] = $intertrading_wid;

    $this->accountSwitcher->switchTo(User::load(1));

    if ($params['outgoing']) {
      // Props to generate a transaction.
      $props['payer'] = $intertrading_wid;
      $props['payee'] = $this->walletIdFromfragment($params['seller_id']);
      // Prepare the params to send back.
      $holder = Wallet::load($props['payee'])->getHolder();
      $params['seller_name'] = $holder->label();
      $params['seller_email'] = $holder->getEmail();
      $params['seller_xname'] = $this->siteConfig->get('name');
      $params['seller_country'] = $this->country;
    }
    else {
      // Props to generate a transaction.
      $props['payee'] = $intertrading_wid;
      $props['payer'] = $this->walletIdFromfragment($params['buyer_id']);
      // Prepare the params to send back.
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
        // Skip some constraints.
        if ($violation->getPropertyPath() == 'payee' and $violation->getConstraint() instanceof CanPayIn) {
          continue;
        }
        elseif ($violation->getPropertyPath() == 'payer' and $violation->getConstraint() instanceof CanPayOut) {
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
   * Try to identify a wallet from a string.
   *
   * @param string $fragment
   *   A bit of text.
   */
  public function walletIdfromFragment($fragment) {
    $wids = $this->walletEntityQuery
      ->condition('payways', Wallet::PAYWAY_AUTO, '<>')
      ->condition('name', '%' . $fragment . '%', 'LIKE')
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
   * Given the exchange id, return its intertrading wallet id.
   *
   * Clumsy but the only other way I know is to create a new db table just for
   * storing occasional wallet settings.
   *
   * @param int $local_exchange_id
   *   Id of an exchange entity (not sure yet which type that might be).
   *
   * @return int
   *   A wallet ID.
   *
   * @throws \Exception
   */
  public function getIntertadingWalletFromExchangeId($local_exchange_id) {
    foreach ($this->intertradingSettings->getAll() as $wid => $settings) {
      if ($settings['login'] == $local_exchange_id) {
        return $wid;
      }
    }
    throw new \Exception('No intertrading wallet for ' . $local_exchange_id);
  }

}
