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
   *
   * @see mcapi_signatures_mcapi_transaction_load().
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
   * Sign a transaction if the users signature is required
   *
   * Change the state if no more signatures are left.
   *
   * @param int $uid
   *   ID of the user who is signing - defaults to the current user
   */
  public function sign($uid = NULL) {
    if (is_null($uid)) {
      $uid = $this->currentUser->id();
    }
    if (isset($this->transaction->signatures[$uid]) and empty($this->transaction->signatures[$uid])) {
      $this->transaction->signatures[$uid] = REQUEST_TIME;
      // Set the state to finished if there are no outstanding signatures.
      if (array_search(0, $this->transaction->signatures) === FALSE) {
        $this->transaction->set('state', 'done');
      }
    }
  }

  /**
   * Fulfil all remaining signatures on the transaction (admin only).
   */
  public function signOff() {
    foreach ($this->transaction->signatures as $uid => $signed) {
      if (!$signed) {
        $this->sign($uid);
      }
    }
  }

  /**
   * Determine which users are due to sign.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return integer[]
   *   IDs of users.
   */
  public function waitingOn($uid = NULL) {
    if (is_null($uid)) {
      $uid = $this->currentUser->id();
    }
    $uids = [];
    if ($this->transaction->state->target_id == 'pending') {
      foreach ($this->transaction->signatures as $user_id => $signed) {
        if (!$signed) {
          $uids[] = $user_id;
        }
      }
    }
    return $uids;
  }

  /**
   * Determine whether the given user's signature is needed.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return bool
   *   TRUE if the transaction needs the user's signature
   */
  public function isWaitingOn($uid = NULL) {
    if (is_null($uid)) {
      $uid = $this->currentUser->id();
    }
    $uids = [];
    if ($this->transaction->state->target_id == 'pending') {
      foreach ($this->transaction->signatures as $user_id => $signed) {
        if ($user_id == $uid and !$signed) {
          return TRUE;
        }
      }
    }
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
