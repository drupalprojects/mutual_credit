<?php

/**
 * @file
 * Contains \Drupal\mcapi_cc\Endpoint.
 * Handles incoming requests from ClearingCentral
 */

namespace Drupal\mcapi_cc;

use Drupal\mcapi\Entity\Currency;
use Drupal\mcapi\Entity\Wallet;
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

  public function __construct($logger, $site_config, $account_switcher, $entity_query) {
    $this->logger = $logger->get('clearingcentral');
    $this->siteConfig = $site_config;
    $this->accountSwitcher = $account_switcher;
    $this->walletEntityQuery = $entity_query->get('mcapi_wallet');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory'),
      $container->get('config.factory')->get('system.site'),
      $container->get('account_switcher'),
      $container->get('entity.query')
    );
  }




  function receiver()  {
    $post = filter_input_array(INPUT_POST);
    //@temp
    if (!$post) {
      $post = filter_input_array(INPUT_GET);
    }

    if (empty($post['saved'])) {
      $this->makeTransaction($post, $post['outgoing'] == FALSE);
      $response = new Response(NULL, $post['response'] == CEN_SUCCESS ? 201 : 200);
    }
    else {
      $this->finalise($post);
      //nothing should go wrong
      $params['response'] = CEN_SUCCESS;
      $response = new Response(NULL, 200);
    }
    $response->setContent(\Drupal\Component\Utility\UrlHelper::buildQuery($post));
    $response->send();
    exit;
  }

  /**
   * finalise the transaction
   *
   * @param type $params
   *   incoming data from clearing central about the saved transaction
   *
   * @return an http status code
   */
  function finalise(&$params) {
    $transaction = \Drupal::entityTypeManager()
      ->getStorage('mcapi_transaction')
      ->loadByProperties(['uuid' => $params['txid']]);
    $transaction->state->target_id = 'done';
    $transaction->save();
    return 200;
  }

  /**
   * create a new transaction and try to save it
   *
   * @param array $params
   *   data from clearing central about the proposed transaction
   *   'outgoing' means whether the transaction was outgoing from its source
   * @param bool $buyer_is_local
   *
   * @return an http status code
   */
  function makeTransaction(&$params, $buyer_is_local) {

    $props = [
      'uuid' => $params['txid'],
      'type' => 'remote',
      'description' => $params['description']
    ];

    if ($buyer_is_local) {
      $local_exchange_id = $params['buyer_nid'];
      $other_exchange_id = $params['seller_nid'];
      $other_exchange_name = $params['seller_xname'];
      $other_user_name = $params['seller_name'];
    }
    else {
      $local_exchange_id = $params['seller_nid'];
      $other_exchange_id = $params['buyer_nid'];
      $other_exchange_name = $params['buyer_xname'];
      $other_user_name = $params['buyer_name'];
    }

    //store this for when we need to view the transaction
    \Drupal::keyValue('exchangeNames')
      ->set($other_exchange_id, $other_exchange_name);

    if ($intertrading_wid = $this->getIntertadingWalletFromExchangeId($local_exchange_id)) {
      $intertrading_settings = \Drupal::keyValue('clearingcentral')->get($intertrading_wid);
      $currency = Currency::load($intertrading_settings['curr_id']);
      list($parts[1], $parts[3]) = explode('.', number_format($params['amount'], 2));
      $props['worth'] = [
        [
          'curr_id' => $currency->id(),
          'value' => $currency->unformat($parts)
        ]
      ];
      $props['creator'] = $intertrading_wid;

      $this->accountSwitcher->switchTo(\Drupal\user\Entity\User::load(1));
      $country =\Drupal::config('system.date')->get('country.default');
      if ($buyer_is_local) {
        $props['payee'] = $intertrading_wid;
        $props['payer'] = $this->walletIdFromfragment($params['buyer_id']);
        $holder = Wallet::load($props['payer'])->getHolder();
        $params['buyer_name'] = (string)$holder->label();
        $params['buyer_email'] = $holder->getEmail();
        $params['buyer_xname'] = $this->siteConfig->get('name');
        $params['buyer_country'] = $country;
      }
      else {
        $props['payer'] = $intertrading_wid;
        $props['payee'] = $this->walletIdFromfragment($params['seller_id']);
        $holder = Wallet::load($props['payee'])->getHolder();
        $params['seller_name'] = (string)$holder->label();
        $params['seller_email'] = $holder->getEmail();
        $params['seller_xname'] = $this->siteConfig->get('name');
        $params['seller_country'] = $country;
      }
      $transaction = Transaction::create($props);
      $transaction->state->target_id = 'done';
      $transaction->remote_exchange_id = $other_exchange_id;
      $transaction->remote_user_id = $other_exchange_name;
      $transaction->remote_user_name = $other_user_name;
    }
    else {
      $this->logger->error('Could not identify intertrading wallet connected to '.$other_exchange_id);
      //send something back to the server
      $params['response'] = 3;
      return;
    }

    $violations = $transaction->validate();
    if (count($violations)) {
      foreach ($violations as $violation) {
        if ($violation->getPropertyPath() == 'payee' and $violation->getConstraint() instanceOf CanPayIn) {
          continue;
        }
        elseif($violation->getPropertyPath() == 'payer' and $violation->getConstraint() instanceOf CanPayOut) {
          continue;
        }
        $bad = TRUE;
        $this->logger->error($violation->getMessage());
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

  function walletIdfromFragment($fragment) {
    $wids = $this->walletEntityQuery
      ->condition('payways', Wallet::PAYWAY_AUTO, '<>')
      ->condition('name', '%'.$fragment.'%', 'LIKE')
      ->execute();
    if (count($wids) > 1) {
      $this->logger->error('Found more than one wallet matching fragment '.$fragment);
    }
    elseif (empty($wids)) {
      $this->logger->error('Found more than one wallet matching fragment '.$fragment);
    }
    return reset($wids);
  }

  //clumsy but the only other way I know is to create a new db table jsut for storing occaisional wallet settings
  function getIntertadingWalletFromExchangeId($local_exchange_id) {
    foreach (\Drupal::keyValue('clearingcentral')->getAll() as $wid => $settings) {
      if ($settings['login'] == $local_exchange_id) {
        return $wid;
      }
    }
    throw new \Exception('No intertrading wallet for '.$local_exchange_id);
  }
}
