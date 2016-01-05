<?php

/**
 * @file
 * Contains \Drupal\mcapi_cc\Endpoint.
 * Handles incoming requests from ClearingCentral
 */

namespace Drupal\mcapi_cc;

use Drupal\mcapi\Entity\Wallet;
use Drupal\mcapi\Entity\Transaction;
use Drupal\system\Controller\SystemController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns responses for Wallet routes.
 */
class Endpoint extends SystemController {
  
  private $logger;
  
  public function __construct($logger) {
    $this->logger = $logger->get('clearingcentral');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory')
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
    $transaction->state->target_id = TRANSACTION_STATE_FINISHED;
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
      'quantity' => $params['amount'],
      'type' => 'remote',
      'state' => TRANSACTION_STATE_HANGING,
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
    
    //brutal hack to get the wallet id from it clearingcentral settings
    $wid = \Drupal::database()->select('key_value', 'kv')
      ->fields('kv', ['name'])
      ->condition('collection', 'clearingcentral')
      ->condition('value', '%'.$local_exchange_id.'%', 'LIKE')
      ->execute()->fetchField();
      
    if ($wid) {
      $intertradingWallet = Wallet::load($wid);
      $intertrading_settings = \Drupal::keyValue('clearingcentral')->get($wid);
      $props['worth'] = [
        [
          'curr_id' => $intertrading_settings['curr_id'],
          'value' => $params['amount']
        ]
      ];
      $props['creator'] = $wid;
      if ($buyer_is_local) {
        $props['payee'] = $intertradingWallet->id();
        $props['payer'] = $params['buyer_id'];
        $params['buyer_name'] = Wallet::load($props['payer'])->getHolder()->label();
      }
      else {
        $props['payer'] = $intertradingWallet->id();
        $props['payee'] = $params['seller_id'];
        $params['seller_name'] = Wallet::load($props['payee'])->getHolder()->label();
      }
      
      $transaction = Transaction::Create($props);
      
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
        $this->logger->error($violation);
      } 
      $params['response'] = 5;
    }
    else {
      $params['response'] = CEN_SUCCESS;
      $transaction->save();
    }
  }
  

}