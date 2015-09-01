<?php

/**
 * @file
 * Contains \Drupal\mcapi_exchanges\Controller\ExchangeController.
 */

namespace Drupal\mcapi_exchanges\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\mcapi_exchanges\ExchangeInterface;

/**
 * Returns responses for Exchange routes.
 */
class ExchangeController extends ControllerBase {

  /**
   * This isn't actually called by the router at the moment
   * @param EntityInterface $mcapi_exchange
   *
   * @return array
   *  A render array
   */
  public function page(ExchangeInterface $mcapi_exchange) {
    return $this->buildPage($mcapi_exchange);
  }

  /**
   * The _title_callback for the mcapi.exchange.view route.
   *
   * @param EntityInterface $mcapi_exchange
   *
   * @return string
   *   The page title.
   */
  public function pageTitle(ExchangeInterface $mcapi_exchange) {
    return SafeMarkup::checkPlain($mcapi_exchange->label());
  }

  /**
   * Builds an exchange page render array.
   *
   * @param EntityInterface $mcapi_exchange
   *
   * @return array
   *   a renderable array
   */
  public function buildPage(ExchangeInterface $mcapi_exchange) {
    return array(
      'exchanges' => $this->entityManager()
        ->getViewBuilder('mcapi_exchange')
        ->view($mcapi_exchange)
    );
  }
  /**
   * show a list of transactions between this exchange and others
   *
   * @param ExchangeInterface $mcapi_exchange
   *
   * @return array
   *   a renderable array
   */
  public function intertrading_wallet(ExchangeInterface $mcapi_exchange) {
    //@todo show the view of the intertrading wallet.
    $wallet = $mcapi_exchange->intertrading_wallet();
    return views_embed_view('wallet_statement', 'embed_1', $wallet->id());
  }
}
