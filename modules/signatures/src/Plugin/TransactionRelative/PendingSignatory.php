<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\TransactionRelative\PendingSignatory
 */

namespace Drupal\mcapi_signatures\Plugin\TransactionRelative;

use Drupal\mcapi\Plugin\TransactionRelativeInterface;
use Drupal\mcapi\TransactionInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Defines a payee relative to a Transaction entity.
 *
 * @TransactionRelative(
 *   id = "pending_signatory",
 *   label = @Translation("Pending signatory"),
 *   description = @Translation("Users whose signature the transaction is awaiting")
 * )
 */
class PendingSignatory extends PluginBase implements TransactionRelativeInterface {

  /**
   * {@inheritdoc}
   */
  public function isRelative(TransactionInterface $transaction, AccountInterface $account) {
    return $this->database->select($account->id(), $transaction->signatories) && $transaction->signatories[$account->id()] == 0;
  }

  /**
   * {@inheritdoc}
   */
  public function condition(QueryInterface $query) {

  }

  /**
   * {@inheritdoc}
   */
  public function getUsers(TransactionInterface $transaction) {
    return db_select('mcapi_signatures', 's')->fields('s', ['uid'])
      ->condition('serial', $transaction->serial->value)
      ->condition('signed', 0)
      ->execute()->fetchCol();
  }

}
