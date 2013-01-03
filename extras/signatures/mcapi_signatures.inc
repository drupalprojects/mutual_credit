<?php


/*
 * do callback derived from hook_transaction_operations
 */
function transaction_operation_do_sign($serial) {
  transaction_sign($serial, $GLOBALS['user']->uid);
}
/*
 * access callback derived from hook_transaction_operations
 */
function transaction_operation_access_sign($callbacks, $transaction) {
  if ($transaction->state != TRANSACTION_STATE_PENDING) return FALSE;
  return _transaction_check_access_callbacks($callbacks, $transaction);
}

/*
 * do callback derived from hook_transaction_operations
 */
function transaction_operation_do_signoff($serial) {
  $needed = array_filter(mcapi_get_signatories($serial));
  foreach (array_keys($needed) as $uid) {
    transaction_sign($serial, $uid);
  }
}
/*
 * access callback derived from hook_transaction_operations
 */
function transaction_operation_access_signoff($callbacks, $transaction) {
  if ($transaction->state != TRANSACTION_STATE_PENDING) return FALSE;
  return _transaction_check_access_callbacks($callbacks, $transaction);
}


/*
 * do callback derived from hook_transaction_operations
 */
function transaction_operation_do_undo_pending($serial) {
  transactions_state(array($serial), TRANSACTION_STATE_ERASED);
}
/*
 * access callback derived from hook_transaction_operations
 */
function transaction_operation_access_undo_pending($callbacks, $transaction) {
  if ($transaction->state != TRANSACTION_STATE_PENDING) return FALSE;
  return _transaction_check_access_callbacks($callbacks, $transaction);
}




function mcapi_signall_confirm_form_submit($form, &$form_state) {
  mcapi_sign(transactions_needing_sig_of_user($form_state['values']['uid']), array($form_state['values']['uid']));
  $form_state['redirect'] = 'user';
}



function list_pending_for_uid($uid, $currcodes = array()) {
  return list_waiting_on_uid($uid, $currcodes) + list_waiting_not_on_uid($uid, $currcodes);
}

function list_waiting_on_uid($uid, $currcodes = array()) {
  $output = array();
  $serials = transactions_needing_sig_of_user($uid);
  if ($serials) {
    $transactions = transactions_load(array(), array('serial' => $serials));
    if (count($transactions) > 1) {
      $output[] = array(
        '#theme' => 'link',
        '#text' => t('Sign all'),
        '#path' => "user/$uid/signall",
        '#options' => array(
          'attributes' => array(),
          'html' => FALSE
        )
      );
    }
    $output[] = transaction_view($transactions, 'token');
  }
  return $output;
}
function list_waiting_not_on_uid($uid, $currcodes = array()) {
  $output = array();
  $serials = db_query(
    "SELECT t.serial FROM {mcapi_transactions} t
     RIGHT JOIN {mcapi_signatures} s ON t.serial = s.serial
     WHERE t.state = :pending
       AND (t.payer = $uid OR t.payee = $uid)
       AND (s.uid = $uid AND s.pending = 0)",
    array(':pending' => TRANSACTION_STATE_PENDING)
  )->fetchCol();
  if ($serials) {
    $transactions = transactions_load(array(), array('serial' => $serials));
    $output[] = transaction_view($transactions, 'token');
  }
  return $output;
}


/*
 * transaction operation submit callback
 * resaves signatories from the transaction object and changes state if all are signed
 */
function transaction_sign($serial, $uid = NULL) {
  if (!$uid) $uid = $GLOBALS['user']->uid;
  //remove signatures one at a time
  //only the first transaction in the cluster can have signatures
  $transaction = transaction_load($serial);
  if ($transaction->pending_signatures[$uid]) {
    $transaction->pending_signatures[$uid] = 0;
    //this might change the state of the transaction
    mcapi_signatures_save($transaction);
    $remaining = array_filter($transaction->pending_signatures);
    if (empty($remaining)) {
      drupal_set_message(t('Transaction @serial is signed off', array('@serial' => $transaction->serial)));
      module_invoke_all('transaction_signed_off', $transaction);
    }
  }
  else {
    //access control should prevent this from happening, unless perhaps the page is refreshed
    drupal_set_message(t('Transaction #@serial is already signed by @name',
      array('@serial' => $transaction->serial, '@name' => user_load($uid)->name)
    ));
  }

}


/*
 * write the signature for each transaction to the database.
 * $transaction->pending signatures is an array
 * array(
 *   $uid => $signature_needed BOOLEAN
 * )
 * Every value should be 1 when the transaction is created
 */
function mcapi_signatures_save($transaction) {
  $old_state = $transaction->state;
  //need to check property 'pending_signatures' because it may not have been declared
  if (!empty($transaction->pending_signatures)) {
    //save all the signatures whether or not they have been signed
    foreach ($transaction->pending_signatures as $uid => $pending) {
      $values[] = "( $transaction->serial, $uid, ". intval($pending)." )";
    }
    db_query("REPLACE INTO {mcapi_signatures} (serial, uid, pending) VALUES ". implode(', ', $values));
  }
  $new_state = count(array_filter($transaction->pending_signatures)) ? TRANSACTION_STATE_PENDING : TRANSACTION_STATE_FINISHED;
  if ($new_state != $old_state) {
    //change the state of the loaded object, then change the state in the db
    $transaction->state = $new_state;
    //because the transaction is already written, and to improve integrity, we reset the state here
    transactions_state(
      array($transaction->serial),
      $new_state
    );
  }
}