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
    $message = transaction_sign(entity_load('mcapi_transaction', $serial), $account);
  }
  drupal_set_message($message);
  $form_state['redirect_route'] = array(
  	'route_name' => 'user.page'
  );
}


function list_pending($account = NULL) {
  if (is_null($account)) {
    $account = \Drupal::currentUser();
  }
  $output = array();
  if ($mixed) {
    $serials = transaction_filter(array('state' => TRANSACTION_STATE_PENDING, 'involving' => $account->id()));
    return mcapi_view_sentences($serials);
  }
  else {
    //couldn't be bothered with a theme callback for this
    $waiting_on = list_waiting_on_uid($account->id());
    if ($waiting_on) {
      $waiting_on['#prefix'] = '<strong>'.t('Waiting on you:').'</strong><br />';
    }
    $waiting_not_on = list_waiting_not_on_uid($account->id());
    if ($waiting_not_on) {
      $waiting_not_on['#prefix'] = '<br /><strong>'.t('Waiting on partner:').'</strong><br />';
    }
    if (count($waiting_on || count($waiting_not_on))) {
      return array(
        '#prefix' => '<div class = "user-pending">',
        'waiting on' => $waiting_on,
        'waiting_not_on' => $waiting_not_on,
        '#suffix' => '</div>'
      );
    }
  }
}

function list_waiting_on_uid($uid) {
  $output = array();
  $serials = transactions_needing_sig_of_user($uid);
  if ($serials) {
    if (count($serials) > 1) {
      //add a link to sign all the users transactions at once.
      //actually need to check permissions here!!!
      $output['signall'] = array(
        '#theme' => 'link',
        '#text' => t('Sign all'),
        '#path' => "user/".$uid."/signall",
        '#options' => array(
          'attributes' => array(),
          'html' => FALSE
        )
      );
    }
    foreach ($serials as $serial) {
      $transaction = entity_load('mcapi_transaction', $serial);
      $output[] = entity_view($transaction, 'sentence');
    }
    return $output;
  }
}
//returns a render array
function list_waiting_not_on_uid($uid) {
  $waiting_on = $output = array();
  $serials = \Drupal::entityManager()
  ->getStorage('mcapi_transaction')
  ->filter(array('involving' => $uid, 'state' => TRANSACTION_STATE_PENDING));
  foreach ($serials as $serial) {
    foreach (entity_load('mcapi_transaction', $serial)->signatures as $uid => $signed) {
      if ($signed) continue;
      if ($uid == \Drupal::currentUser()->id())continue;
      $waiting_on[] = $serial;
    }
  }
  foreach ($waiting_on as $serial) {
    $transaction = entity_load('mcapi_transaction', $serial);
    $output[] = entity_view($transaction, 'sentence');
  }
  return $output;
}


function transactions_needing_sig_of_user($uid) {
  //assumes data integrity that all transactions referenced are in pending state
  return db_select("mcapi_signatures", 's')
    ->fields('s', array('serial'))
    ->condition('uid', $uid)
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