<?php

namespace Drupal\mcapi_exchanges\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\group\Entity\GroupInterface;
use Drupal\mcapi_exchanges\Exchanges;

/**
 * Returns responses for Exchange routes.
 */
class ExchangeController extends ControllerBase {

  /**
   * Show a list of transactions between this exchange and others.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity whose intertrading wallet we want to see.
   *
   * @return array
   *   A renderable array.
   */
  public function showIntertradingWallet(GroupInterface $group) {
    // @todo show the view of the intertrading wallet.
    $wallet = Exchanges::getIntertradingWallet($group);
    return views_embed_view('wallet_statement', 'embed_1', $wallet->id());
  }

  public function intertradingWalletTitle(GroupInterface $group) {
    return 'Intertrading wallet';
  }

}
