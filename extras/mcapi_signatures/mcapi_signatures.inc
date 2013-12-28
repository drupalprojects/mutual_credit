<?php


function mcapi_signall($form, $form_state, $account) {
  $serials = transactions_needing_sig_of_user($account);
  $form['preview'] = transaction_view(
    entity_load('mcapi_transaction', transaction_filter(array('serial' => $serials))),
    \Drupal::config('mcapi.misc')->get('sentence_template')
  );
  $form['account'] = array(
    '#type' => 'value',
    '#value' => $account
  );
  $form['buttons']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Sign all')
  );
  return $form;
}

function mcapi_signall_submit($form, &$form_state) {
  $account = $form_state['values']['account'];
  foreach (transactions_needing_sig_of_user($account) as $serial) {
    $message = transaction_sign(transaction_load($serial), $account);
  }
  drupal_set_message($message);
  $form_state['redirect'] = 'user';
}


function list_pending_for_uid($account, $currcodes = array()) {
  $output = array();
  //couldn't be bothered with a theme callback for this
  $waiting_on = list_waiting_on_uid($account, $currcodes);
  $waiting_not_on = list_waiting_not_on_uid($account, $currcodes);
  if (count($waiting_on || count($waiting_not_on))) {
    return array(
      '#prefix' => '<div class = "user-pending">',
      'waiting on' => $waiting_on,
      'waiting_not_on' => $waiting_not_on,
      '#suffix' => '</div>'
    );
  }
}

function list_waiting_on_uid($account, $currcodes = array()) {
  $output = array();
  $serials = transactions_needing_sig_of_user($account);
  if ($serials) {
    if (count($serials) > 1) {
      //add a link to sign all the users transactions at once.
      //actually need to check permissions here!!!
      $output[] = array(
        '#theme' => 'link',
        '#text' => t('Sign all'),
        '#path' => "user/".$account->id()."/signall",
        '#options' => array(
          'attributes' => array(),
          'html' => FALSE
        )
      );
    }
    $transactions = array();
    foreach ($serials as $serial) {
      $transaction = transaction_load($serial);
      $transactions[$transaction->xid] = $transaction;
    }
    $output[] = transaction_view(
      $transactions,
      \Drupal::config('mcapi.misc')->get('sentence_template')
    );
  }
  return $output;
}
//returns a render array
function list_waiting_not_on_uid($account, $currcodes = array()) {
  $volitional_transactions = array();
  $serials = transaction_filter(array('involving' => $account->id(), 'state' => TRANSACTION_STATE_PENDING));
  $transactions = transaction_load(array_unique($serials));
  foreach ($transactions as $serial => $transaction) {
    $signatures = $transaction->signatures;
    unset($signatures[$account->id()]);
    $waiting_on = array_filter($signatures);
    if ($waiting_on) {
      $volitional_transactions[$transaction->xid] = $transaction;
    }
  }
  if (count($volitional_transactions)) {
    return transaction_view($volitional_transactions, \Drupal::config('mcapi.misc')->get('sentence_template'));
  }
  return array();
}

function transactions_needing_sig_of_user($account) {
  //assumes data integrity that all transactions referenced are in pending state
  return db_select("mcapi_signatures", 's')
    ->fields('s', array('serial'))
    ->condition('uid', $account->id())
    ->condition('signed', '')
    ->execute()
    ->fetchCol();
}

//send the mail notifying users they have a mail to sign
function signature_send_initial_notification($transaction, $config) {
  if (!$config['notify']) return;
  $params = array(
    'transaction' => $transaction,
    'mail_template' => variable_get('mcapi_sign_notify_template', array()),
  );
  $recipients = array_keys(array_filter($transaction->signatures));
  //send the mail, using the module settings for this transaction
  foreach(user_load_multiple($recipients) as $account) {
    $params['user'] = $account;
    drupal_mail('mcapi_signatures', 'blah', $account->mail, user_preferred_language($account), $params);
  }
}

/*
 * implements hook_mail
 */
function mcapi_signatures_mail($key, &$message, $params) {
  $options = array('language' => $message['language'], 'sanitize' => FALSE);
  $message['subject'] = token_replace($params['mail_template']['subject'], $params, $options);
  $message['body'][] = token_replace($params['mail_template']['body'], $params, $options);
}