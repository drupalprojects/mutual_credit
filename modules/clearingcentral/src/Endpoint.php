<?php

/**
 * @file
 * Contains \Drupal\mcapi_cc\Endpoint.
 * Handles incoming requests from ClearingCentral
 */

namespace Drupal\mcapi_cc;

use Drupal\system\Controller\SystemController;
use Drupal\Component\Utility\UrlHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns responses for Wallet routes.
 */
class Endpoint extends SystemController {

  private $logger;

  public function __construct($logger) {
    $this->logger = $logger->get('Clearing Central');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory')
    );
  }

  /**
   * Receive the message as server B from clearing central.
   *
   * Validate and save the transaction.
   *
   * @return array transaction || int
   *   The transaction in the remote format or an error code
   */
  function receiver()  {
    $post = filter_input_array(INPUT_POST);

    $key = $post['outgoing'] ? 'seller_nid' : 'buyer_nid';
    $found = 0;
    foreach (\Drupal::keyValue('clearingcentral')->getAll() as $intertrading_wid => $setting) {
      if ($setting['exchange_id'] == $post[$key]) {
        $found = 1;
        break;
      }
    }
    if (!$found) {
      // Unknown error because clearing central routed this request here because
      // that exchange is registered here.
      $params['response'] = 0;
      $this->logger->error("Unable to find intertrading wallet for exchange: ".$params[$key] . print_r($params, 1));
    }

    \Drupal::service('mcapi_cc.clearing_central')->receive($post);

    $return_code = $post['response'] == CEN_SUCCESS ? 201 : 200;
    $response = new Response(NULL, $return_code);
    $content = UrlHelper::buildQuery($post);

    $response->setContent($content)->send();
    exit;
  }

}
