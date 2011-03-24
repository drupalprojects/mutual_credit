<?php

/*
 * This file should be loaded whenever a currency or transaction is viewed or modified
 */

/*
 * The transaction entity has its own CRUD operations
 */
class TransactionEntityController extends DrupalDefaultEntityController {
  //this converts the serialized data property into an array
  //not needed I think, because we should add proper fields for that
  /*

/*
 * Implements hook_user_view
 */
function mcapi_user_view($account) {
  module_load_include('inc', 'mcapi', 'entities');
  $balances = entity_get_controller('transaction')->get_balances($account->uid);
  $account->content['mcapi'] = array(
    '#title' => t('Monetary activity'),
    '#type' => 'user_profile_category',
    'balances' => array(
      '#theme' => 'balances',
      '#title' => t('Balances'),
      '#balances' => $balances,
    )
  );
}
  public function _________load($xids = array(), $conditions = array()) {
    $transactions = parent::load($xids, $conditions);
    foreach($transactions as $key => $transaction) {
      $transactions[$key]->data = unserialize($transaction->data);
    }
    return $transactions;
  }
  /*
   * Validation ideally would happen by using the save function with $write set to FALSE
   * Instead we just check the balances here/
   */
  public function validate($transaction){
    $currency = currencies_load($transaction->cid);
    //if the limit check fails, it will be thrown back and the rest of validate won't run
    mcapi_check_limits($transaction->payer_uid, $transaction->payee_uid, -$transaction->quantity, $transaction->cid);
    $key = isset($transaction->xid) ? $transaction->xid : NULL;
    try {
      //temporarily change the transaction type so that validated transaction left hanging DO leave a trace.
      $state = $transaction->state;
      $transaction->state = TRANSACTION_STATE_VALIDATING;
      entity_get_controller('transaction')->save($transaction, $currency->write_validate);
      $transaction->state = $state;
    }
    catch (Exception $e){
      //if the validation fails, we add the error message to the transaction object
      $transaction->data['error'] = $e; //THIS IS NOT TESTED
    }
    //the db rolled back but now we need to change this object back
    if (!$currency->write_validate) {
      $transaction->xid = $key;
    }
  }

  /*
   * update or insert the saved transaction
   * expects a single, validated transaction object
   */
  public function save($transaction, $write = FALSE) {
    //this is only used to fix what I think is a Drupal bug, see mcapi_field_attach_presave
    //removes the 'add more' item from fields with infinite cardinality
    $instances = array();
    $instances = _field_invoke_get_instances('transaction', 'transaction', array('deleted' => FALSE));
    foreach($instances as $instance) {
      $field = field_info_field($instance['field_name']);
      if ($field['cardinality'] == -1) {
        unset($transaction->{$instance['field_name']}[LANGUAGE_NONE]['add_more']);
      }
    }//end of bugfix

    //clear any error messages which were created in validation
    unset ($transaction->data['error']);
    //somebody should tell me if the methodology is good here.
    $t = db_transaction();
    //unfortunately the rollback doesn't roll back the auto-increment.
    $function = isset($transaction->xid) && $transaction->xid ? 'update' : 'insert';
    try {
      entity_get_controller('transaction')->$function($transaction, TRUE);
    }
    catch (Exception $e) {
      $t->rollback();
      watchdog_exception('mcapi', $e);
      throw $e;
    }
    if (!$write) {
      $t->rollback();
      //for some reason these public functions all have their arguments passed by reference
      //so we must be careful to unset the xid if we were only testing
    }
    else {
      module_invoke_all('entity_'.$function, 'transaction', $transaction);
    }
  }

  /*
   * merely set the state of the exchange, not delete the row
   * there is no error checking on this.
   */
  public function erase($transaction) {
    $currency = currencies_load($transaction->cid);
    $currency = currencies_load($transaction->cid);
    switch ($currency->delete_mode) {
      case '0':
        throw new Exception(t('Deletions not allowed'));
        break;
      case '1':
        $transaction->state = TRANSACTION_STATE_ERASED;
        //db_query("UPDATE {mcapi_transactions} SET state = :state, data = :data WHERE xid = :xid",
        //  array(':state' => $transaction->state, ':data' => serialize($transaction->data), ':xid' => $transaction->xid)
        //);
        drupal_write_record('mcapi_transactions', $transaction, 'xid');
        break;
      case '2':
        $query = db_delete('mcapi_transactions')
        ->condition('xid', $transaction->xid)
        ->execute();
        break;
    }
    try {
      balances_refresh($transaction->cid, array($transaction->payer_uid, $transaction->payee_uid));
    }
    catch (Exception $e) {
      drupal_set_message($e, 'warning');
    }
    module_invoke_all('entity_delete', 'transaction', $transaction);
  }


  /*
   * Get a list of transactions, filtered by $conditions
   * keys in $conditions array are:
   * * states - the transaction state constant
   * * cid - the currency id
   * * uid (the owner of the transaction)
   * * id a participant in the transaction
   */
  public function transaction_list($conditions = array()){
    $query = db_select('mcapi_transactions', 'x');
    if (isset($conditions['states'])){
      $query->condition('state', $conditions['states']);
    }
    if (isset($conditions['cid'])) {
      $query->condition('cid', $conditions['cid']);
    }
    if (isset($conditions['uid'])) {
      $query->condition('uid', $conditions['uid']);
    }
    if (isset($conditions['id'])) {
      $query->condition(
         db_or()
         ->condition('payer_uid', $conditions['id'])
         ->condition('payee_uid', $conditions['id'])
       );
    }
    return $query->fields('x')->execute()->fetchAll();
  }
  /*
   * retrieve all the balances for a user
   * $cids can be an array or a currency id
   * every cid asked for will return list of values, even if it doesn't exist in the db.
   */
  public function get_balances($uid, $cids = array()) {
    if (is_numeric($cids)) {
      $cids = array($cids);
    }
    $query = db_select('mcapi_cache', 'c')->fields('c')->condition('uid', $uid);
    foreach ($cids as $cid) {
      $query->condition('cid', $cid);
    }
    $results = $query->execute()->fetchAllAssoc('cid', PDO::FETCH_ASSOC);
    //in case the requested row doesn't exist in the table, make a row of zero values
    foreach ($cids as $cid) {
      if (!isset($results[$cid])) $results[$cid] = array('cleared_balance' => 0 , 'pending_dif' => 0, 'gross_in' => 0, 'gross_out' => 0, 'max' => NULL, 'min' => NULL, 'count' => 0);
    }
    return $results;
  }

  /*
   * write an transaction (and its dependents) to the database, refreshing the balances
   */
  function insert($transaction) {
    $table = 'mcapi_transactions';
    $success = drupal_write_record($table, $transaction);
    if (!$success) {
      throw new Exception(t('failed to write to @name table', array('@name' => $table)));
    }
    field_attach_insert('transaction', $transaction);
    //make a unique list of all the affected and update their balances
    $refreshers[] = $transaction->payer_uid;
    $refreshers[] = $transaction->payee_uid;

    //assumes all the transactions were in the same currency
    balances_refresh($transaction->cid, array_unique($refreshers));
  }

  /*
   *
   * The GVA engine will erase and create a new record rather that actually updating a record
   * This update function copies that
   * An alternative would be to use the revision control provided by the entity
   * Transactions with derived transactions, do not update, but automatically erase and insert,
   * TODO We could use the versions table here, since it is included already
   */
  function update($transaction) {
    $currency = currencies_load($transaction->cid);
    switch($currency->update_mode) {
      case 0:
        throw new Exception(t('Updates not allowed'));
        break;
      case 1:
        //delete the old, remove the xid and insert it
        $old = clone $transaction;
        //$transaction->data['was'] = $transaction->xid;//this is where we link the new to the old.
        unset($transaction->xid);
        $this->insert($transaction); //will give a new xid
        $old->data['became'] = $transaction->xid;
        $this->erase($old);
        break;
      case 2:
        //while there are no 'derived' transactions, we are dealing with one only
        drupal_write_record('mcapi_transactions', $transaction, 'xid');
        field_attach_update('transaction', $transaction);
        break;
    }
    balances_refresh($transaction->cid, array($transaction->payer_uid, $transaction->payee_uid));
  }

}

/*
 * The currency entity has its own CRUD operations
 */
class CurrencyEntityController extends DrupalDefaultEntityController {

  public function load($cids = array(), $conditions = array()) {
    $currencies = parent::load($cids, $conditions);
    foreach($currencies as $key => $currency) {
      $data = unserialize($currencies[$key]->data);
      foreach ($data as $name => $value) {
        $currencies[$key]->$name = $value;
      }
      //make the divisions available as an array, from the string they are stored and edited as.
      $currencies[$key]->divisions = currency_divisions_array($currency->divisions_conf);
    }
    return $currencies;
  }
  public function loadAll() {
    //web have to get the ids of all the currencies before we can use the standard entity load function
    $cids = db_select('mcapi_currencies', 'c')->fields('c', array('cid'))->execute()->fetchCol('cid');
    return $this->load($cids);
  }

  public function save($currency) {
    $function = isset($currency->cid) && $currency->cid ? 'update' : 'insert';
    $currency = $this->$function($currency, TRUE);
    module_invoke_all('entity_'.$function, 'currency', $currency);
  }
  
  function insert($currency) {
    $table = 'mcapi_currencies';
    $success = drupal_write_record($table, $currency);
    if (!$success) {
      throw new Exception(t('failed to write to @name table', array('@name' => $table)));
    }
    field_attach_insert('currency', $currency);
  }

  function update($currency) {
    drupal_write_record('mcapi_currencies', $currency, array('cid'));
    field_attach_update('currency', $currency);
  }
/*
  function delete($cid) {
    db_query("DELETE FROM {mcapi_currencies} WHERE cid = :cid", array(':cid' => $cid));
  }
*/
}


/*
 * Alternative approach to mcapi_check_limits
 * Assuming the transaction is recorded and the balances are updated, but it can all be rolled back
 */
function test_balances($uid, $cid) {
  $balances = db_select('mcapi_cache')
  ->fields(array('cleared_balance', 'pending_dif', 'max', 'min'))
  ->condition('uid', $uid)
  ->condition('cid', $cid)
  ->execute()->getAssoc();
  extract($balances);

  $deficit = $min - $cleared_balance + $pending_dif;
  if ($deficit > 0) {
    $message = t('Transaction takes @username !quant below the minimum balance of !min',
      array(
        '@username' => strip_tags(theme('username', array('account' => user_load($payer_uid)))),
        '!quant' => theme('money', array('quantity' => $deficit, 'cid' => $cid, $richtext = FALSE)),
        '!min' => theme('money', array('quantity' => $min, 'cid' => $cid, $richtext = FALSE))
      )
    );
  }
  else {
    $excess = $cleared_balance + $pending_dif -$max;
    if ($excess > 0) {
      $message = t('Transaction takes @username !quant above the maximum balance of !max',
        array(
          '@username' => strip_tags(theme('username', array('account' => user_load($uid)))),
          '!quant' => theme('money', array('quantity' => $surplus, 'cid' => $cid, $richtext = FALSE)),
          '!max' => theme('money', array('quantity' => $max, 'cid' => $cid, $richtext = FALSE))
        )
      );
    }
  }
  if ($message) {
    throw new Exception($message);
  }
}

/*
 * limit checker
 * checks an account to see if the proposed difference will violate the balance limits
 * this is also called during transaction validation
 */
function mcapi_check_limits($payer_uid, $payee_uid, $difference, $cid) {
  module_load_include('inc', 'mcapi');
  $message = '';

  //check that the payee isn't going over their max limit
  $balances = entity_get_controller('transaction')->get_balances($payee_uid, $cid);
  $payee_projected = $balances[$cid]['cleared_balance'] + $balances[$cid]['pending_dif'] + $difference;
  $max = $balances[$cid]['max'];
  if (is_numeric($max) && (float)$difference > 0) {
    $surplus = $payee_projected - $max;
    if ($surplus > 0) {
      $message .= t('Transaction takes @username !quant above the maximum balance of !max',
        array(
          '@username' => strip_tags(theme('username', array('account' => user_load($uid)))),
          '!quant' => theme('money', array('quantity' => $surplus, 'cid' => $cid, $richtext = FALSE)),
          '!max' => theme('money', array('quantity' => $max, 'cid' => $cid, $richtext = FALSE))
        )
      );
    }
  }
  //check that the payer isn't going below their min limit
  $balances = entity_get_controller('transaction')->get_balances($payer_uid, array($cid));
  $payer_projected = $balances[$cid]['cleared_balance'] + $balances[$cid]['pending_dif'] + $difference;
  $min = $balances[$cid]['min'];
  if (is_numeric($min) && (float)$difference < 0) {
    $deficit = $min - $payer_projected;
    if ($deficit > 0) {
      $message .= t('Transaction takes @username !quant below the minimum balance of !min',
        array(
          '@username' => strip_tags(theme('username', array('account' => user_load($payer_uid)))),
          '!quant' => theme('money', array('quantity' => $deficit, 'cid' => $cid, $richtext = FALSE)),
          '!min' => theme('money', array('quantity' => $min, 'cid' => $cid, $richtext = FALSE))
        )
      );
    }
  }
  if (strlen($message)) {
    throw new Exception($message);
  }
}


function currency_divisions_array($string) {
  if (strpos($string, '|')) {
    $lines = explode("\n", $string);
    foreach($lines as $line) {
      $array = explode('|', $line);
      $allowed[$array[0]] = $array[1];
    }
    return $allowed;
  }
  elseif ($string == '00') {
    return '00';
  }
}



//refreshes a cache of all the transactions, calculates the running balances and other totals
//will be done automatically by GVA engine, so this function won't be needed
function balances_refresh($cid, $uids) {
  foreach($uids as $uid) {
    $conditions['id'] = $uid;
    $conditions['cid'] = $cid;
    $history = entity_get_controller('transaction')->transaction_list($conditions);

    //now iterate through the transactions making running balances
    $cleared_balance = $pending_dif = $gross_out = $gross_in = 0;
    while (list($key, $transaction) = each($history)) {
      //add to or subtract from the running balances
      if ($transaction->payer_uid == $uid) {
        if ($transaction->state == TRANSACTION_STATE_FINISHED) {
          $cleared_balance -= $transaction->quantity;
        }
        else {
          $pending_dif -= $transaction->quantity;
        }
        $gross_out += $transaction->quantity;
      }
      else {
        if ($transaction->state == TRANSACTION_STATE_FINISHED) {
          $cleared_balance += $transaction->quantity;
        }
        else {
          $pending_dif -= $transaction->quantity;
        }
        $gross_in += $transaction->quantity;
      }
      $transaction->balance = $cleared_balance;
      //add the transaction to the list if requred
      if ($transaction->state == TRANSACTION_STATE_FINISHED) {
        $transactions[$transaction->xid] = $transaction;
      }
    }
    $query = db_merge('mcapi_cache')
    ->key(array('cid' => $cid, 'uid' => $uid))
    ->fields(
       array('cleared_balance', 'pending_dif', 'gross_in', 'gross_out', 'count'),
       array($cleared_balance, $pending_dif, $gross_in, $gross_out, count($history))
    );
    $query->execute();
  }
}
