<?php

use \Drupal\mcapi\Entity\WalletInterface;
use \Drupal\mcapi\Entity\TransactionInterface;
use \Drupal\Core\Session\AccountInterface;
use Drupal\mcapi\Entity\Transaction;


/**
 * sign a transaction
 * change the state if no more signatures are left
 * would be nice if this was in a decorator class so $transaction->sign($account) is possible
 * @param TransactionInterface $transaction
 * @param AccountInterface $account
 * @return TransactionInterface
 */
function transaction_sign(TransactionInterface $transaction, AccountInterface $account) {
  if (array_key_exists($account->id(), $transaction->signatures)) {
    $transaction->signatures[$account->id()] = REQUEST_TIME;
    //set the state to finished if there are no outstanding signatures
    if (array_search(0, $transaction->signatures) === FALSE) {
      $transaction->set('state', TRANSACTION_STATE_FINISHED);
    }
  }
  return $transaction;
}

/*
 * get all the pending transactions for one wallet or Wallet Owner
 *
 * @param ContentEntityInterface $entity
 *   the wallet or wallet or wallet owner entity
 *
 * @return array
 *   the serial numbers of the pending transactions
 */
function list_pending(Drupal\Core\Entity\ContentEntityInterface $entity) {
  $conditions = array('state' => TRANSACTION_STATE_PENDING);
  if ($entity instanceOf WalletInterface) {
    $conditions['involving'] = array($entity->id());
  }
  else {
    $conditions['involving'] = \Drupal::EntityManager()->getStorage('mcapi_wallet')->getOwnedWalletIds($entity);
  }
  $serials = \Drupal::EntityManager()
  ->getStorage('mcapi_transaction')
  ->filter($conditions);
  return array_unique($serials);
}

function mcapi_signall($form, $form_state, $account) {
  $form['preview'] = transaction_view(
    Transaction::loadBySerials(transactions_needing_sig_of_user($account)),
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

function mcapi_signall_submit($form, $form_state) {
  $values = $form_state->getValues();
  foreach (transactions_needing_sig_of_user($values['account']) as $serial) {
    $message = transaction_sign(Transaction::load($serial), $values['account']);
  }
  drupal_set_message($message);
  $form_state->setRedirect('user.page');
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
      $transaction = Transaction::load($serial);
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
    foreach (Transaction::load($serial)->signatures as $uid => $signed) {
      if ($signed) continue;
      if ($uid == \Drupal::currentUser()->id())continue;
      $waiting_on[] = $serial;
    }
  }
  foreach ($waiting_on as $serial) {
    $transaction = Transaction::load($serial);
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


/**
 * implements template_preprocess_THEMEHOOK
 */
function template_preprocess_mcapi_signatures(&$vars) {
  $plugin = transaction_transitions('sign');
  foreach ($vars['transaction']->signatures as $uid => $signed) {
    $account = User::load($uid);
    $vars['signatures'][$uid] = array(
        'account' => $account,
        'class' => 'signature '. (empty($signed) ? 'pending' : 'signed'),
        'name' =>  $account->getUsername(),
    );
  }
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
  foreach(User::loadMultiple($recipients) as $account) {
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