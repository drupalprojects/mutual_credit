<?php

namespace Drupal\mcapi_signatures;

use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\mcapi\Entity\Type;
use Drupal\Core\Session\AccountInterface;
use Drupal\mcapi\Mcapi;

/**
 * Signatures service.
 *
 * Loads, signs and gives info relating to transaction signatures.
 */
class Signatures {

  private $transaction;
  private $currentUser;
  private $database;

  /**
   * Constructor.
   */
  public function __construct(AccountInterface $current_user, $database) {
    $this->currentUser = $current_user;
    $this->database = $database;
  }

  /**
   * Initialise this service by telling it which transaction to work on.
   */
  public function setTransaction(TransactionInterface $transaction) {
    $this->transaction = $transaction;
    return $this;
  }

  /**
   * Respond to the transaction being loaded.
   *
   * Read from the signatory table and add them to transaction entities.
   */
  public static function load($entities) {
    $xids = [];
    foreach ($entities as $xid => $e) {
      if ($e->parent->value == 0) {
        $xids[$e->serial->value] = $xid;
      }
    }
    // We load these regardless of settings, just in case settings have changed
    // leaving some transactions invisibly pending.
    // no matter everything will be cached.
    $signatures = \Drupal::database()->select('mcapi_signatures', 's')
      ->fields('s', ['serial', 'uid', 'signed'])
      ->condition('serial', array_keys($xids), 'IN')
      ->execute()->fetchAll();
    foreach ($signatures as $sig) {
      $entities[$xids[$sig->serial]]->signatures[$sig->uid] = $sig->signed;
    }
  }

  /**
   * Respond to the transaction being deleted.
   *
   * Delete from the signatory table.
   */
  public function delete() {
    db_delete('mcapi_signatures')
      ->condition('serial', $this->transaction->serial->value)
      ->execute();
  }

  /**
   * Respond to the transaction being inserted.
   *
   * Write the signatories to the signatory table.
   */
  public function insert() {
    $q = $this->database->insert('mcapi_signatures')
      ->fields(['serial', 'uid', 'signed']);
    foreach ($this->transaction->signatures as $uid => $signed_unixtime) {
      $q->values([$this->transaction->serial->value, $uid, $signed_unixtime]);
    }
    $q->execute();
  }

  /**
   * Respond to the transaction being updated.
   *
   * Update the signatory table.
   */
  public function update() {
    // New signatures cannot be added, only existing ones changed.
    foreach ($this->transaction->signatures as $uid => $signed) {
      $this->database->update('mcapi_signatures')
        ->fields(['signed' => $signed])
        ->condition('serial', $this->transaction->serial->value)
        ->condition('uid', $uid)
        ->execute();
    }
  }

  /**
   * Add signatures to the transaction according to the configured relatives.
   */
  public function addSignatures() {
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
   * Sign a transaction.
   *
   * Change the state if no more signatures are left.
   *
   * @param int $uid
   *   ID of the user who is signing.
   */
  public function sign($uid = NULL) {
    if (!$uid) {
      $uid = $this->currentUser->id();
    }
    if (array_key_exists($uid, $this->transaction->signatures)) {
      $this->transaction->signatures[$uid] = REQUEST_TIME;
      // Set the state to finished if there are no outstanding signatures.
      if (array_search(0, $this->transaction->signatures) === FALSE) {
        $this->transaction->set('state', 'done');
      }
    }
  }

  /**
   * Which users are due to sign? or whether the passed user is due to sign.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return boolean | integer[]
   *   IDs of users.
   *
   * @todo rewrite this as two functions
   */
  public function waitingOn($uid = 0) {
    $uids = [];
    if ($this->transaction->state->target_id == 'pending') {
      foreach ($this->transaction->signatures as $user_id => $signed) {
        if (!$signed) {
          if ($user_id == $uid) {
            return TRUE;
          }
          $uids[] = $user_id;
        }
      }
    }
    return $uids;
  }

  /**
   * Get the transactions needing signature of a given user.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return int[]
   *   Serial numbers of the transactions.
   *
   * @todo this whole module needs to handle the database better, maybe by
   * decorating the transactionStorage.
   */
  public static function transactionsNeedingSigOfUser($uid) {
    // Assumes all transactions referenced are in pending state.
    return \Drupal::database()->select("mcapi_signatures", 's')
      ->fields('s', array('serial'))
      ->condition('uid', $uid)
      ->condition('signed', 0)
      ->execute()
      ->fetchCol();
  }

}
