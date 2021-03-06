<?php

/**
 * @see hook_transaction_operations().
 */
function _mcapi_signatures_transaction_operations() {
  //note that strings are given in English and translated elsewhere, as in the menu system
  return array(
    'sign' => array(
      'title' => 'Sign',
      'description' => 'Sign a pending transaction',
      'sure' => 'Are you sure you want to sign?',
      'submit callback' => 'transaction_op_sign',
      'access callback' => 'transaction_op_sign_access',
      'form callback' => TRUE,
      'access form' => '',//this has no configuration, not even the default callback
      'weight' => 2,
      'filepath' => 'mcapi_signatures.inc'
    ),
    'signoff' => array(
      'title' => 'Sign off',
      'description' => "Sign a pending transaction on behalf of all signatories, so that it moved into 'finished' state.",
      'sure' => 'Are you sure you want to sign off this transaction?',
      'submit callback' => 'transaction_op_signoff',
      'access callback' => 'transaction_op_signoff_access',
      'form callback' => TRUE,
      'weight' => 5,
      'filepath' => 'mcapi_signatures.inc'
    ),
    'undo_pending' => array(
      'title' => 'Cancel',
      'description' => 'Cancel a pending transaction',
      'sure' => 'Are you sure you want to cancel this pending transaction?',
      'submit callback' => 'transaction_op_undo',
      'access callback' => 'transaction_op_undo_pending_access',
      'form callback' => 'transaction_op_undo_form',
      'weight' => 9,
      'redirect' => 'user',
      'filepath' => 'mcapi_signatures.inc'
    ),
  );
  //these are just here for the potx module
  t('Sign');
  t('Sign a pending transaction');
  t('Are you sure you want to sign?');
  t('Sign off');
  t("Sign a pending transaction on behalf of all signatories, so that it moved into 'finished' state.");
  t('Are you sure you want to sign off this transaction?');
  t('Cancel');
  t('Cancel a pending transaction');
  t('Are you sure you want to cancel this pending transaction?');
}

/**
 * operation callback from mcapi_signatures_transaction_operations
 */
function transaction_op_sign($op, $transaction) {
  $message = transaction_sign($transaction, $GLOBALS['user']->uid);
  return transaction_view($transaction);
}
/**
 * Operation access callback from mcapi_signatures_transaction_operations.
 *
 * This doesn't rely on user settings at all.
 */
function transaction_op_sign_access($op, $transaction, $currency) {
  if ($transaction->state == TRANSACTION_STATE_PENDING &&
    property_exists($transaction, 'pending_signatures') &&
    array_key_exists($GLOBALS['user']->uid, $transaction->pending_signatures) &&
    $transaction->pending_signatures[$GLOBALS['user']->uid]) {
    return TRUE;
  }
}

/**
 * Operation callback from mcapi_signatures_transaction_operations
 */
function transaction_op_signoff($op, $transaction) {
  $needed = array_filter(mcapi_get_signatories($transaction->serial));
  foreach (array_keys($needed) as $uid) {
    $message = transaction_sign($transaction, $uid);
  }
  return transaction_view($transaction, 'certificate');
}
/**
 * Operation access callback from mcapi_signatures_transaction_operations
 */
function transaction_op_signoff_access($op, $transaction, $currency) {
  if ($transaction->state != TRANSACTION_STATE_PENDING) {
    return FALSE;
  }
  return _transaction_check_access_callbacks($currency->access_operations[$op], $transaction);
}

/**
 * Operation access callback from mcapi_signatures_transaction_operations
 */
function transaction_op_undo_pending_access($op, $transaction, $currency) {
  if ($transaction->state != TRANSACTION_STATE_PENDING) {
    return FALSE;
  }
  return _transaction_check_access_callbacks($currency->access_operations[$op], $transaction);
}

/**
 * Menu callback
 *
 * Confirm you want to sign all pending transactions for a member.
 */
function mcapi_signall($form, &$form_state, $account) {
  $serials = transactions_needing_sig_of_user($account->uid);
  $form['preview'] = transaction_view(
    entity_load('transaction', array_keys(transaction_filter(array('serial' => $serials)))),
    str_replace('[transaction:links]', '', variable_get('mcapi_sentence', mcapi_sentence_default()))
  );
  $form['uid'] = array(
    '#type' => 'hidden',
    '#value' => $account->uid
  );
  $form['buttons']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Sign all')
  );
  return $form;
}

/**
 * Form submission callback.
 *
 * Sign all a user's pending transactions
 */
function mcapi_signall_submit($form, &$form_state) {
  $uid = &$form_state['values']['uid'];
  foreach (transactions_needing_sig_of_user($uid) as $serial) {
    $message = transaction_sign(transaction_load($serial), $uid);
  }
  drupal_set_message($message);
  $form_state['redirect'] = 'user';
}


/**
 * Utility
 *
 * Check whether a transaction type needs signatures and change the state accordingly
 */
//changes the status in the transaction form according to the settings for the transaction_type
function _set_form_state_to_pending(&$form, &$form_state) {
   //read the settings and set the $form['state'] hidden widget
  $type = isset($form['type']['#value']) ? $form['type']['#value'] : $form['type']['#default_value'];//yuk
  if (in_array($type, _get_signable_transaction_types())) {
    $config = _signature_settings_default($type);
    if ($config['participants'] || $config['countersignatories']) {
      $form['state'] = array(
        '#type' => 'value',
        '#value' => TRANSACTION_STATE_PENDING
      );
    }
  }
}

/**
 * Content callback
 *
 * Show all pending transactions pertaining to a certain user.
 */
function list_pending_for_uid($uid, $currcodes = array()) {
  $output = array();
  // Couldn't be bothered with a theme callback for this
  $waiting_on = list_waiting_on_uid($uid, $currcodes);
  $waiting_not_on = list_waiting_not_on_uid($uid, $currcodes);
  if (count($waiting_on) ||count($waiting_not_on)) {
    return array(
      '#prefix' => '<div class = "user-pending">',
      'pending' => $waiting_on + $waiting_not_on,
      '#suffix' => '</div>'
    );
  }
}

/**
 * Content callback
 *
 * Show pending transactions waiting for the signature of a certain user.
 *
 * @param int $uid
 *   The user who needs to sign
 *
 * @return array
 *   A renderable array of the transaction sentences
 */
function list_waiting_on_uid($uid) {
  $serials = transactions_needing_sig_of_user($uid);
  if ($serials) {
    $transactions = array();
    foreach ($serials as $serial) {
      $transaction = transaction_load($serial);
      if (!transaction_operation_access('view', $transaction)) {
        continue;
      }
      $transactions[$transaction->xid] = $transaction;
    }
    if ($transactions) {
      return transaction_view($transactions, variable_get('mcapi_sentence', mcapi_sentence_default()));
    }
  }
  return array();
}

/**
 * Content callback
 *
 * Show a user's pending transactions as sentences which are waiting for the
 * signature for another user.
 */
function list_waiting_not_on_uid($uid, $currcodes = array()) {
  $volitional_transactions = array();
  $serials = transaction_filter(array('involving' => $uid, 'state' => TRANSACTION_STATE_PENDING));
  $transactions = transaction_load(array_unique($serials));
  foreach ($transactions as $serial => $transaction) {
    if (transaction_operation_access('view', $transaction)) {
      $signatures = $transaction->pending_signatures;
      unset($signatures[$uid]);
      $waiting_on = array_filter($signatures);
      if ($waiting_on) {
        $volitional_transactions[$transaction->xid] = $transaction;
      }
    }
  }
  if (count($volitional_transactions)) {
    return transaction_view($volitional_transactions, variable_get('mcapi_sentence', mcapi_sentence_default()));
  }
  return array();
}

/**
 * Utility
 *
 * Get all the transaction ids waiting for a certain user's signature
 *
 * @param int $uid
 *
 * @return int[]
 */
function transactions_needing_sig_of_user($uid) {
  //assumes data integrity that all transactions referenced are in pending state
  return db_select("mcapi_signatures", 's')
    ->fields('s', array('serial'))
    ->condition('uid', $uid)
    ->condition('pending', TRUE)
    ->execute()
    ->fetchCol();
}
/**
 * Transaction operation submit callback.
 *
 * Unmarks a signature as pending. Resaves signatories from the transaction
 * object and changes state if all are signed.
 */
function transaction_sign($transaction, $uid) {
  if ($transaction->pending_signatures[$uid]) {
    $transaction->pending_signatures[$uid] = 0;
    //this might change the state of the transaction
    $remaining = mcapi_signatures_write($transaction);
    if (empty($remaining)) {
      module_invoke_all('transaction_signed_off', $transaction);
      return(t('Transaction @serial is signed off',
        array('@serial' => $transaction->serial))
      );
    }
  }
  else {
    //access control should prevent this from happening, unless perhaps the page is refreshed
    return(t('Transaction #@serial is already signed by @name',
      array('@serial' => $transaction->serial, '@name' => user_load($uid)->name)
    ));
  }

}


/**
 * write the signature for each transaction to the database and change the state of the transaction
 * $transaction->pending signatures is an array
 * array(
 *   $uid1 => $signature_needed BOOLEAN
 *   $uid2 => $signature_needed BOOLEAN
 * )
 * Every value should be 1 when the transaction is created
 */
function mcapi_signatures_write($transaction) {
  $old_state = $transaction->state;
  //need to check property 'pending_signatures' because it may not have been declared
  if (!empty($transaction->pending_signatures)) {
    //save all the signatures whether or not they have been signed
    foreach ($transaction->pending_signatures as $uid => $pending) {
      db_merge('mcapi_signatures')
        ->key(array('serial' => $transaction->serial, 'uid' => $uid))
        ->fields(array('pending' => $pending))->execute();
    }
  }
  //set the state to finished if there are no outstanding signatures
  if ($old_state == TRANSACTION_STATE_PENDING && !count(array_filter($transaction->pending_signatures))) {
    //change the state of the loaded object, then change the state in the db
    $transaction->state = TRANSACTION_STATE_FINISHED;
    //save and act on the state change
    //transaction_update($transaction, TRANSACTION_STATE_PENDING);
    entity_get_controller('transaction')->update($transaction, TRANSACTION_STATE_FINISHED);
  }
  return array_filter($transaction->pending_signatures);
}

/**
 * Menu form callback.
 *
 * Configure signatures for each transaction type and pending notification mail.
 */
function mcapi_signatures_config($form, &$form_state) {
  module_load_include('inc', 'mcapi');
  $w = 0;
  $form = array('#tree' => TRUE);
  foreach (_get_signable_transaction_types() as $type) {
    $var_name = 'mcapi_signatures_'.$type;
    $settings = @$form_state['values'][$var_name] ? $form_state['values'][$var_name] : _signature_settings_default($type);
    $form[$var_name] = array(
      '#title' => $type,
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#id' => $type,
      'participants' => array(
        '#title' => t('Both participants must sign'),
        '#description' => t('Logged in user signs automatically'),
        '#type' => 'checkbox',
        '#default_value' => $settings['participants']
      ),
      'notify' => array(
        '#title' => t("Send 'pending' notification"),
        '#description' => t('Use the email template on this page to notify all signatories'),
        '#type' => 'checkbox',
        '#default_value' => $settings['notify'],
        '#weight' => 1,
        '#states' => array(
          'visible' => array(
            ':input[name="notify"]' => array('value' => 1)
          )
        ),
      ),
      'countersignatories' => array(
        '#title' => t('Counter-signers'),
        '#description' => t('Choose one or more users'),
        '#type' => 'user_chooser_few',
        '#callback' => 'user_chooser_segment_roles',
        '#args' => array(DRUPAL_AUTHENTICATED_RID),
        '#default_value' => $settings['countersignatories'],
        '#multiple' => TRUE,
        '#required' => FALSE,
        '#weight' => 2,
      ),
      '#weight' => $w++
    );
  }
  $mail_def = variable_get('mcapi_sign_notify_template', array());
  $form['mcapi_sign_notify_template'] = array(
    '#title' => t('Signature notification'),
    '#description' => implode(' ', array(
      t('Compose the email notifying signatories that a transaction needs their attention.'),
      t('Use the tokens provided.'),
      t('To notify users when a transaction is completed, use the action and triggers: !link',
        array('!link' => l('admin/config/system/actions', 'admin/config/system/actions')))
    )),
    '#type' => 'fieldset',
    'subject' => array(
      '#title' => t('Subject'),
      '#type' => 'textfield',
      '#default_value' => isset($mail_def['subject']) ? $mail_def['subject'] : t('Your signature is required'),
      '#required' => TRUE,
    ),
    'body' => array(
      '#title' => t('Body'),
      '#type' => 'textarea',
      '#default_value' => isset($mail_def['body']) ? $mail_def['body'] : '[transaction:url]',
      '#required' => TRUE,
      '#weight' => 1
    ),
    '#weight' => $w++
  );
  $form['token_tree'] = array(
    '#theme' => 'token_tree',
    '#token_types' => array('transaction', 'user', 'site'),
    '#global_types' => FALSE,
    '#weight' => $w++
  );
  return system_settings_form($form);
}

//redirected from module file.
function mcapi_sig_transaction_create($transaction, $really = 0) {
  //check the settings for this content type to see what signatures are required if any.
  $config = _signature_settings_default($transaction->type);
  $transaction->pending_signatures = array();
  if ($transaction->state == TRANSACTION_STATE_PENDING) {
    //put the signatures in the transaction object,
    if ($config['participants']) {
      $transaction->pending_signatures[$transaction->payer] = $transaction->creator == $transaction->payer ? 0 : 1;
      $transaction->pending_signatures[$transaction->payee] = $transaction->creator == $transaction->payee ? 0 : 1;
    }
    $config['countersignatories']= array_filter((array)$config['countersignatories']);
    foreach($config['countersignatories'] as $counter_uid) {
      $transaction->pending_signatures[$counter_uid] = $transaction->creator == $counter_uid ? 0 : 1;
    }
    if ($really) {
      //save the signatures
      mcapi_signatures_write($transaction);
      signature_send_initial_notification($transaction, $config);
    }
  }
  elseif ($transaction->state == TRANSACTION_STATE_UNDONE) {
    db_delete('mcapi_signatures')->condition('serial', $transaction->serial)->execute();
  }
}

//send a mail to each signatory notifying users their signature is needed
function signature_send_initial_notification($transaction, $config) {
  if ($config['notify']) {
    $params = array(
      'transaction' => $transaction,
      'mail_template' => variable_get('mcapi_sign_notify_template', array('subject' => '', 'body' => '')),
    );
    $recipients = array_keys(array_filter($transaction->pending_signatures));
    //send the mail, using the module settings for this transaction
    foreach(user_load_multiple($recipients) as $account) {
      drupal_mail(
        'mcapi_signatures',
        'pending', 
        $account->mail,
        user_preferred_language($account),
        $params + array('user' => $account)
      );
    }
  }
}

/**
 * Implements hook_mail().
 */
function mcapi_signatures_mail($key, &$message, $params) {
  if ($key == 'pending') {
    $options = array('language' => $message['language'], 'sanitize' => FALSE);
    $message['subject'] = token_replace($params['mail_template']['subject'], $params, $options);
    $message['body'][] = token_replace($params['mail_template']['body'], $params, $options);
  }
}

function _signature_settings_default($type) {
  if ($setting = variable_get('mcapi_signatures_'.$type, 0)) return $setting;
  return array(
    'participants' => FALSE,
    'countersignatories' => array(),
    'notify' => FALSE
  );
}

//utility function
//gets all the transaction types and excludes a few that we know aren't suitable for configuring
function _get_signable_transaction_types() {
  $types = array_diff(mcapi_get_types(), array('dependent', 'undo', 'undone', 'remote'));
  sort($types);
  return $types;
}
