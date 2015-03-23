<?php

use \Drupal\mcapi\WalletInterface;
use \Drupal\mcapi\TransactionInterface;
use \Drupal\Core\Session\AccountInterface;
use Drupal\mcapi\Entity\Transaction;
use Drupal\Core\Template\Attribute;


/**
 * sign a transaction
 * change the state if no more signatures are left
 * would be nice if this was in a decorator class so $transaction->sign($account) is possible
 * @param TransactionInterface $transaction
 * @param AccountInterface $account
 */
function transaction_sign(TransactionInterface $transaction, AccountInterface $account) {
  if (array_key_exists($account->id(), $transaction->signatures)) {
    $transaction->signatures[$account->id()] = REQUEST_TIME;
    //set the state to finished if there are no outstanding signatures
    if (array_search(0, $transaction->signatures) === FALSE) {
      $transaction->set('state', TRANSACTION_STATE_FINISHED);
    }
  }
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
  elseif ($wids = \Drupal::EntityManager()->getStorage('mcapi_wallet')->getOwnedIds($entity)) {
    $conditions['involving'] = $wids;
  }
  $serials = \Drupal::EntityManager()
  ->getStorage('mcapi_transaction')
  ->filter($conditions);
  return array_unique($serials);
}

function mcapi_signall($form, $form_state, $account) {
  $serials = transactions_needing_sig_of_user($account);
  $form['preview'][] = \Drupal::entityManager()
    ->getViewBuilder('mcapi_transaction')
    ->viewMultiple(Transaction::loadBySerials($serials), 'sentence');

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
    $transaction = current(Transaction::loadBySerials(array($serial)));
    transaction_sign($transaction, $values['account']);
  }
  $transaction->save();
  drupal_set_message($message);
  $form_state->setRedirect('user.page');
}


function list_waiting_on_uid($uid) {
  $output = [];
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
          'attributes' => new Attribute(),
          'html' => FALSE
        )
      );
    }
  }
  return $output + \Drupal::entityManager()
    ->getViewBuilder('mcapi_transaction')
    ->viewMultiple(Transaction::loadBySerials($serials), 'sentence');
}
//returns a render array
function list_waiting_not_on_uid($uid) {
  $entityManager = \Drupal::entityManager();
  $waiting_on = $output = [];
  $serials = $entityManager
    ->getStorage('mcapi_transaction')
    ->filter(array('involving' => $uid, 'state' => TRANSACTION_STATE_PENDING));
  foreach ($serials as $serial) {
    foreach (Transaction::load($serial)->signatures as $uid => $signed) {
      if ($signed) continue;
      if ($uid == \Drupal::currentUser()->id())continue;
      $waiting_on[] = $serial;
    }
  }
  return $output + $entityManager
    ->getViewBuilder('mcapi_transaction')
    ->viewMultiple(Transaction::loadBySerials($waiting_on), 'sentence');
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
  foreach ($vars['transaction']->signatures as $uid => $signed) {
    $account = User::load($uid);
    $vars['signatures'][$uid] = array(
      'account' => $account,
      'class' => 'signature '. (empty($signed) ? 'pending' : 'signed'),
      'name' =>  $account->getUsername(),
    );
  }
}
