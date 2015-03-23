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
  function autocomplete(Request $request) {
    //there are three different ways offered here, none of which is perfect
    //because of the different ways that wallet names can be construed
    $results = [];

    $conditions = [];

    $param = explode(',', \Drupal::routeMatch()->getParameter('exchanges'));
    if ($exchanges = array_filter($param)) {
      $conditions['exchanges'] = $exchanges;
    }

    $string = $request->query->get('q');

    //there is no need to handle multiple values because the javascript of the widget
    //handles all stuff before the last comma.
    if (is_numeric($string)) {
      $conditions['wid'] = array($string);
    }
    //deal with the case where a wid has been entered with a hash
    elseif (substr($string, 0, 1) == '#' && is_numeric($num = substr($string, 1))) {
      $conditions['wid'] = $num;
    }
    else {
      $conditions['fragment'] = $string;
    }

    //TODO inject this
    return $this->returnWallets(\Drupal\mcapi\Storage\WalletStorage::filter($conditions));
  }

}
