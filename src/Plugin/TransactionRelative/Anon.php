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
 *   id = "anon",
 *   label = @Translation("Anonymous users"),
 *   description = @Translation("Anyone on the Internet")
 * )
 */
class Anon extends PluginBase implements TransactionRelativeInterface {

  /**
   * {@inheritdoc}
   */
  public function isRelative(TransactionInterface $transaction, AccountInterface $account) {
    return $account->isAnonymous();
  }

  /**
   * {@inheritdoc}
   */
  public function condition(QueryInterface $query) {

  }

  public function access(){}
}
