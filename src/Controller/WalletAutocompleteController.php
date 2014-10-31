<?php

/**
 * @file
 * Contains \Drupal\mcapi\Controller\WalletAutocompleteController.
 */

namespace Drupal\mcapi\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\mcapi\Entity\Wallet;

/**
 * Returns responses for Transaction routes.
 * @todo Make this better
 */
class WalletAutocompleteController {

  /*
   * get a list of all wallets in exchanges of which the the current user is a member.
   *
   */
  function autocomplete(Request $request) {
    //there are three different ways offered here, none of which is perfect
    //because of the different ways that wallet names can be construed
    $results = array();

    $conditions = array();

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
    $wids = \Drupal\mcapi\Storage\WalletStorage::filter($conditions);

    if (empty($wids)) {
      $json = array(
        array(
          'value' => '',
          'label' => '--'.t('No matches').'--'
        )
      );
    }
    else {
      foreach (Wallet::loadMultiple($wids) as $wallet) {
        $json[] = array(
          'value' => $wallet->label(NULL, FALSE),//maybe shorter
          'label' => $wallet->label(NULL, TRUE)
          //both values should end in the hash which is needed for parsing later
        );
      }
    }
    return new JsonResponse($json);
  }

}
