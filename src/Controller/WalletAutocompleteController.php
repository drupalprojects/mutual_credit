<?php

/**
 * @file
 * Contains \Drupal\mcapi\Controller\WalletAutocompleteController.
 *
 */

namespace Drupal\mcapi\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Controller\ExceptionController;

/**
 * Returns responses for Transaction routes.
 * @todo Make this better
 */
class WalletAutocompleteController {

  /*
   * get a list of all wallets in exchanges which the the current user is a member of
   *
   */
  function autocomplete(Request $request) {
    //there are three different ways offered here, none of which is perfect
    //because of the different ways that wallet names can be construed
    $results = array();

    //the keys of the exchanges of which the current user is a member
    $exchanges = array();
    if ($request->attributes->get('_route') != 'mcapi.wallets.autocomplete_all') {
      module_load_include('inc', 'mcapi');
      $exchanges = array_keys(referenced_exchanges());
    }
    $string = $request->query->get('q');
    $conditions = array();

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
    if ($exchanges) {
      $conditions['exchanges'] = $exchanges;
    }
    $wids = \Drupal\mcapi\Storage\WalletStorage::filter($conditions);

    if (empty($wids)) {
      $json = array(
        array(
          'value' => '',
          'label' => '['.t('No matches'.']')
        )
      );
    }
    else {
      foreach (entity_load_multiple('mcapi_wallet', $wids) as $wallet) {
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
