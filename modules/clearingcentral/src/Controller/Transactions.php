<?php

namespace Drupal\mcapi_cc\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\group\Entity\GroupInterface;

/**
 * Returns a list of intertrading transactions.
 */
class Transactions extends ControllerBase {

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
    return views_embed_view('wallet_statement', 'embed_1', intertrading_wallet_id());
  }

  public function intertradingWalletTitle(GroupInterface $group) {
    return 'Intertrading wallet';
  }

}
