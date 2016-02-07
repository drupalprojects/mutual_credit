<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\TransactionRelativeInterface.
 */

namespace Drupal\mcapi\Plugin;

use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\Query\QueryInterface;

interface TransactionRelativeInterface {
  /**
   *
   * @param TransactionInterface $transaction
   * @param AccountInterface $account
   *
   * @return boolean
   *   TRUE if $account is related to the transaction with this plugin
   */
  function isRelative(TransactionInterface $transaction, AccountInterface $account);

  /**
   * Modify a database query on the transaction table to show only the relatives
   *
   * @param QueryInterface $query
   *
   * @note this is not yet implemeted, but should be used in all transaction views.
   */
  function condition(QueryInterface $query);

  /**
   * get the ids of users who are related to the transaction
   *
   * @param TransactionInterface $transaction
   *
   * @return integer[]
   */
  function getUsers(TransactionInterface $transaction);

}
