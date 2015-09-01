<?php

/**
 * @file
 * Contains \Drupal\mcapi_exchanges\Controller\ExchangesWalletAutocompleteController.
 * Mostly copied from \Drupal\mcapi\Controller\WalletAutocompleteController
 */

namespace Drupal\mcapi_exchanges\Controller;

use Drupal\mcapi\Controller\WalletAutocompleteController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\mcapi\Entity\Wallet;

/**
 * Returns responses for Transaction routes.
 * @todo Make this better
 */
class ExchangesWalletAutocompleteController extends WalletAutocompleteController{

  /*
   * get a list of all wallets in exchanges of which the the current user is a member.
   *
   */
  protected function getWalletIds(Request $request) {
    //intersect the available wallets with the wallets in this exchange
    $wids = parent::getWalletids($request);
    
    return array_intersect(
      $wids,
      Self::inExchanges(array_filter($exchanges))
    );
  }

}
