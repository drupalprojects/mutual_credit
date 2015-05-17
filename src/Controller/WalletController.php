<?php

/**
 * @file
 * Contains \Drupal\mcapi\Controller\WalletController.
 *
 */

namespace Drupal\mcapi\Controller;

use Drupal\mcapi\WalletInterface;

/**
 * Returns responses for Wallet routes.
 */
class WalletController {

  /**
   * The _content callback for the entity.mcapi_wallet.canonical route.
   * Provides a transaction history for the wallet
   *
   * This uses a saved view, but note that the view has no access control of itself.
   * Thats why it is done this way, using normal router entity access
   *
   * @return array
   *  a render array
   *
   * @todo check with the page display of this view to see whether the route can access the views argument
   * If not, this workaround is just fine.
   */
  public function log(WalletInterface $mcapi_wallet) {
    return views_embed_view(
      'wallet_statement',
      'embedded_in_route_mcapi_dot_wallet_log',
      $mcapi_wallet->id()
      //could also pass the currency id and the year to the view, but from here we don't know which
    );
  }

  public function pageTitle(WalletInterface $mcapi_wallet = NULL) {
    return $mcapi_wallet->label();
  }

}
