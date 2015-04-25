<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\TransactionRelative\Payer
 */

namespace Drupal\mcapi\Plugin\TransactionRelative;

use Drupal\mcapi\Plugin\TransactionRelativeInterface;
use Drupal\mcapi\TransactionInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Defines a payer relative to a Transaction entity.
 *
 * @TransactionRelative(
 *   id = "manager",
 *   label = @Translation("Accounting manager"),
 *   description = @Translation("users with permission to 'manage stuff'")
 * )
 */
class Manager extends PluginBase implements TransactionRelativeInterface {

  /**
   * {@inheritdoc}
   */
  public function isRelative(TransactionInterface $transaction, AccountInterface $account) {
    if (!$account) mtrace();
    return $account->hasPermission('manage mcapi');
  }

  /**
   * {@inheritdoc}
   */
  public function condition(QueryInterface $query) {

  }
}
