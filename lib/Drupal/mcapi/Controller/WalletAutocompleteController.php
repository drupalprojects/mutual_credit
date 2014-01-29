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

/**
 * Returns responses for Transaction routes.
 * @todo Make this better
 */
class WalletAutocompleteController {

  private $exchanges;
  private $fieldnames;

  function __construct() {
    module_load_include('inc', 'mcapi');
    $this->fieldnames = array_filter(get_exchange_entity_fieldnames());
    //the keys of the exchanges of which the current user is a member
    $this->exchanges = array_keys(referenced_exchanges());
  }

  /*
   * get a list of all wallets in exchanges which the the current user is a member of
   * this is not at all elegant, but then the problem is quite tricky.
   * Would have been more elegant in Drupal 7 where all instances of a field used the same table for data
   */
  function autocomplete(Request $request) {
    //there are three different ways offered here, which will hopefully do...
    $results = array();

    $string = '%'.db_like($request->query->get('q')).'%';
    //so now we have the wallet ids in one array
    if (is_numeric($string)) {//this is easy
      $results = array($string => entity_load('mcapi_wallet', $string));
    }
    elseif(\Drupal::Config('mcapi.wallets')->get('unique_names')) {
      $query = db_select('mcapi_wallets', 'w')
        ->fields('w', array('wid', 'pid', 'entity_type'))
        ->condition('name', $string, 'LIKE')
        ->condition('entity_type', '', '<>');//so we don't get the system wallets
        $rows = $query->execute();
      $results = $this->walletFilter($rows);
    }
    //finally we can do a search on usernames & walletnames only
    else {
      $query = db_select('mcapi_wallets', 'w');
      $query->join('users', 'u', 'w.pid = u.uid');
      $query->leftjoin('user__field_exchanges', 'x', "x.entity_id = u.uid AND w.entity_type = 'user'");
      $wids = $query->fields('w', array('wid'))
        ->condition('field_exchanges_target_id', $this->exchanges)
        ->condition('status', 1)
        ->condition(db_or()
          ->condition('u.name', $string, 'LIKE')
          ->condition('w.name', $string, 'LIKE')
        )
        ->range(0, 10)
        ->execute()->fetchcol();
      $results = entity_load_multiple('mcapi_wallet', $wids);
    }

    foreach ($results as $wallet) {
      $label = $wallet->label();
      //the brackets are used to identify the wallet id at the other end
      $json[] = array('value' => $label .'('.$wallet->id().')', 'label' => $label);
    }
    return new JsonResponse($json);
  }

  /**
   * filter the result rows from the wallets table according to the exchanges of the parent entities
   * @param array $results
   *   each item in the array is an array with wid, entity_id and pid
   * @return array
   *   entities
   */
  function walletFilter(array $results) {
    foreach($results as $result) {
      $by_exchange[$result->entity_type][$result->pid] = $result->wid;
    }
    $hits = array();
    //search each field's table to see if the enties are in the right exchanges
    foreach ($by_exchange as $entity_type => $wids) {
      $field_name = $this->fieldnames[$entity_type];
      $table = $entity_type .'__'.$field_name;
      $hits += db_select($table, 'f')->fields('f', array('entity_id'))
        ->condition($field_name.'_target_id', $this->exchanges)
        ->condition('entity_id', array_keys($wids))
        ->range(0, 10)
        ->execute()->fetchCol();
    }
    $wids = array_diff_key($wids, array_keys($hits));
    return entity_load_multiple('mcapi_wallet', $wids);

  }

  //this is arguably expensive, and is not currently used
  //gets all the valid wids, doing one query for each,
  //but then there's nothing to compare with the passed string!
  /*
  function getAllWids() {
    $wids = array();
    foreach ($this->fieldnames as $entity_type => $field_name) {
      $query = db_select($entity_type .'__'.$field_name, 'f');
      $query->join('mcapi_wallets', 'w', 'w.pid = f.entity_id');
      $wids += $query->fields('w', array('wid'))
      ->condition($field_name.'_'.$target_id, $this->exchanges)
      ->execute()->fetchCol();
    }
    return $wids;
  }
  */
}