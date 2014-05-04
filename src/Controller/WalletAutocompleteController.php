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

  private $exchanges;
  private $fieldnames;

  function __construct() {
    module_load_include('inc', 'mcapi');
    $this->fieldnames = array_filter(get_exchange_entity_fieldnames());
    //the keys of the exchanges of which the current user is a member
    $this->exchanges = array();
    if (\Drupal::request()->attributes->get('_route') != 'mcapi.wallets.autocomplete_all') {
      $this->exchanges = array_keys(referenced_exchanges());
    }
  }

  /*
   * get a list of all wallets in exchanges which the the current user is a member of
   * this is not at all elegant, but then the problem is quite tricky.
   * Would have been more elegant in Drupal 7 where all instances of a field used the same table for data
   */
  function autocomplete(Request $request) {
    //there are three different ways offered here, none of which is perfect
    //because of the different ways that wallet names can be construed
    $results = array();
    $q = $request->query->get('q');
    $string = '%'.db_like($q).'%';
    //so now we have the wallet ids in one array

    if (is_numeric($q)) {
      $wids = array($q);
    }
    else {
      $query = db_select('mcapi_wallets', 'w')->fields('w', array('wid'));
      $namelike = db_or()->condition('w.name', $string, 'LIKE');
      //join the query directly to the exchange entity table, to get the names of exchanges
      $query->leftjoin('mcapi_exchanges', 'ex', "w.pid = ex.id AND ex.open = 1 AND w.entity_type = 'mcapi_exchange'");
      if ($this->exchanges) {
        $query->condition("ex.id", $this->exchanges);
      }
      $namelike->condition('ex.id', $string, 'LIKE');

      //join the query to any other entity tables, via those entities entity reference fields
      //actually you can't join more than one table in different directions
      foreach (get_exchange_entity_fieldnames() as $entity_type => $fieldname) {
        //if fieldname is blank that means the entity_type is mcapi_exchange, which can't reference itself
        if ($entity_type != 'mcapi_exchange') {
          $entity_info = \Drupal::entityManager()->getDefinition($entity_type, TRUE);
          $alias = $entity_type;
          $key = $entity_info['entity_keys']['id'];
          $query->leftjoin($entity_info['base_table'], $alias, "w.pid = $alias.uid");
          $query->leftjoin($entity_type.'__'.$fieldname, "x{$alias}", "x{$alias}.entity_id = {$alias}.{$key}  AND w.entity_type = '$entity_type'");
          //limit the results to open exchanges only
          $query->leftjoin('mcapi_exchanges', "mcapi_exchanges", "x{$alias}.{$fieldname}_target_id = mcapi_exchanges.id AND mcapi_exchanges.open = 1");
          if ($this->exchanges) {
            $query->condition("x{$alias}.{$fieldname}_target_id", $this->exchanges);
          }
          $namelike->condition($alias.'.'.entitynamefield($entity_type), $string, 'LIKE');
        }
      }
      //we know that user is is one of the entities in this query
      $query->condition('user.status', 1)->condition($namelike);

      $wids = $query->range(0, 10)->execute()->fetchcol();
    }
    foreach (entity_load_multiple('mcapi_wallet', $wids) as $wallet) {
      $json[] = array(
        'value' => _mcapi_wallet_autocomplete_value($wallet),
        'label' => $wallet->label()
      );
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
    //search each field's table to see if the entities are in the right exchanges
    foreach ($by_exchange as $entity_type => $wids) {
      $field_name = $this->fieldnames[$entity_type];
      $table = $entity_type .'__'.$field_name;
      $hits += db_select($table, 'f')->fields('f', array('entity_id'))
        ->condition($field_name.'_target_id', $this->exchanges)
        ->condition('entity_id', array_keys($wids))
        ->range(0, 10)
        ->execute()->fetchCol();
    }
    return array_diff_key($wids, array_keys($hits));
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

//hacky solution to help build a db query which checks the name / title of various entities
function entitynamefield($entity_type) {
  switch($entity_type) {
  	case 'user':
  	  return 'name';
  	case 'node':
  	  return 'title';
  	default :
  	  throw new \RuntimeException('Entity type unknown to WalletAutocompleteController: '. $entity_type);
  }

}