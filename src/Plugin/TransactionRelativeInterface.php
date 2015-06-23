<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\TransactionRelativeInterface.
 */

namespace Drupal\mcapi\Plugin;

use Drupal\mcapi\TransactionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\Query\QueryInterface;

interface TransactionRelativeInterface {
  /**
   *
   * @param TransactionInterface $transaction
   * @param AccountInterface $account
   */
  function isRelative(TransactionInterface $transaction, AccountInterface $account);

  /**
   *
   * @param QueryInterface $query
   */
  function condition(QueryInterface $query);

  /**
   *
   * @param array $plugin_names
   *
   * @return integer[]
   */
  function getUsers(TransactionInterface $transaction);

}
