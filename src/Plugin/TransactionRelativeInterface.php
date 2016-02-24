<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\TransactionRelativeInterface.
 */

namespace Drupal\mcapi\Plugin;

use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Database\Query\AlterableInterface;

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
   * @param \Drupal\Core\Database\Query\AlterableInterface $query
   * @param \Drupal\Core\Database\Query\Condition $or_group
   * @param integer $uid
   */
  public function entityViewsCondition(AlterableInterface $query, $or_group, $uid);


  /**
   * Modify a database query on the transaction index table to show only the relatives
   *
   * @param \Drupal\Core\Database\Query\AlterableInterface $query
   * @param \Drupal\Core\Database\Query\Condition $or_group
   * @param integer $uid
   */
  public function indexViewsCondition(\AlterableInterface $query, $or_group, $uid);

  /**
   * get the ids of users who are related to the transaction
   *
   * @param TransactionInterface $transaction
   *
   * @return integer[]
   */
  function getUsers(TransactionInterface $transaction);

}
