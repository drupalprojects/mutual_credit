<?php

namespace Drupal\mcapi\Plugin;

use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Database\Query\Condition;

/**
 * Jnterface for TransactionRelative plugins.
 */
interface TransactionRelativeInterface {

  /**
   * Check whether an $account is related to the user with the current plugin.
   *
   * @param TransactionInterface $transaction
   *   The transaction.
   * @param AccountInterface $account
   *   The user who is candidate to be related.
   *
   * @return bool
   *   TRUE if $account is related to the transaction with this plugin
   */
  public function isRelative(TransactionInterface $transaction, AccountInterface $account);

  /**
   * Filter a db query on the transaction table to show only the relatives.
   *
   * @param AlterableInterface $query
   *   A query being built.
   * @param Condition $or_group
   *   A db condition to augment.
   * @param int $uid
   *   The user id related to the transactions to be selected.
   */
  public function entityViewsCondition(AlterableInterface $query, Condition $or_group, $uid);

  /**
   * Get the ids of users who are related to the transaction.
   *
   * @param TransactionInterface $transaction
   *   The transaction.
   *
   * @return integer[]
   *   The IDs of the user(s) related tothe tramsaction.
   */
  public function getUsers(TransactionInterface $transaction);

}
