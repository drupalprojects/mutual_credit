<?php

/**
 * @file
 * Contains Drupal\mcapi_signatures\Signatures
 * would be nicer if this was in a decorator class enabling things like $transaction->sign($uid);
 */

namespace Drupal\mcapi_signatures;

use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\mcapi\Entity\Type;
use Drupal\Core\Session\AccountInterface;
use Drupal\mcapi\Mcapi;

class Signatures {

  private $transaction;
  private $currentUser;
  private $database;

  function __construct(AccountInterface $current_user, $database) {
    $this->currentUser = $current_user;
    $this->database = $database;
  }

  function setTransaction(TransactionInterface $transaction) {
    $this->transaction = $transaction;
    return $this;
  }

  static function load($entities) {
    $xids = [];
    foreach ($entities as $xid => $e) {
      if ($e->parent->value == 0) {
        $xids[$e->serial->value] = $xid;
      }
    }
    //we load these regardless of settings, just in case settings have changed
    //leaving some transactions invisibly pending.
    //no matter everything will be cached
    $signatures = \Drupal::database()->select('mcapi_signatures', 's')
      ->fields('s', ['serial', 'uid', 'signed'])
      ->condition('serial', array_keys($xids), 'IN')
      ->execute()->fetchAll();
    foreach ($signatures as $sig) {
      $entities[$xids[$sig->serial]]->signatures[$sig->uid] = $sig->signed;
    }
  }

  function delete() {
    db_delete('mcapi_signatures')
      ->condition('serial', $this->transaction->serial->value)
      ->execute();
  }

  function insert() {
    $q = $this->database->insert('mcapi_signatures')
      ->fields(['serial', 'uid', 'signed']);
    foreach ($this->transaction->signatures as $uid => $signed_unixtime) {
      $q->values([$this->transaction->serial->value, $uid, $signed_unixtime]);
    }
    $q->execute();
  }

  function update() {
    //this only allows the signed date to be changed, not new signatures to be added
    foreach ($this->transaction->signatures as $uid => $signed) {
      $this->database->update('mcapi_signatures')
        ->fields(['signed' => $signed])
        ->condition('serial', $this->transaction->serial->value)
        ->condition('uid', $uid)
        ->execute();
    }
  }

  /**
   * add signatures to the transaction according to the configured relatives
   */
  function addSignatures() {
    $relatives = Type::load($this->transaction->type->target_id)
      ->getThirdPartySetting('mcapi_signatures', 'signatures');
    $user_ids = Mcapi::transactionRelatives()
      ->getUsers($this->transaction, $relatives);
    foreach ($user_ids as $uid) {
      $this->transaction->signatures[$uid] = 0;
    }
    return $this;
  }

  /**
   * sign a transaction
   * change the state if no more signatures are left
   * @param int $uid
   */
  function sign($uid = NULL) {
    if (!$uid) {
      $uid = $this->currentUser->id();
    }
    if (array_key_exists($uid, $this->transaction->signatures)) {
      $this->transaction->signatures[$uid] = REQUEST_TIME;
      //set the state to finished if there are no outstanding signatures
      if (array_search(0, $this->transaction->signatures) === FALSE) {
        $this->transaction->set('state', 'done');
      }
    }
  }

  /**
   * Find out which users are due to sign, or whether the passed user is due to sign
   * @param type $uid
   * @return boolean | integer[]
   */
  function waitingOn($uid = 0) {
    $uids = [];
    if ($this->transaction->state->target_id == 'pending') {
      foreach ($this->transaction->signatures as $user_id => $signed) {
        if (!$signed) {
          if ($user_id == $uid) {
            return TRUE;
          }
          $uids[] = $uid;
        }
      }
    }
    return $uids;
  }


  /**
   * @param integer $uid
   * @return string[]
   *   serial numbers of the transactions
   */
  static function transactionsNeedingSigOfUser($uid) {
    //assumes data integrity that all transactions referenced are in pending state
    return \Drupal::database()->select("mcapi_signatures", 's')
      ->fields('s', array('serial'))
      ->condition('uid', $uid)
      ->condition('signed', 0)
      ->execute()
      ->fetchCol();
  }

}
