<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\TransactionRelative\Payer
 */

namespace Drupal\mcapi\Plugin\TransactionRelative;

use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\Plugin\TransactionRelativeInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Defines a payee relative to a Transaction entity.
 *
 * @TransactionRelative(
 *   id = "creator",
 *   label = @Translation("Creator"),
 *   description = @Translation("The user who created the transaction")
 * )
 */
class Creator extends PluginBase implements TransactionRelativeInterface {//does it go without saying that this implements TransitionInterface

  /**
   * {@inheritdoc}
   */
  public function isRelative(TransactionInterface $transaction, AccountInterface $account) {
    return $transaction->creator->entity->id() == $account->id();
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
    return $transaction->creator->entity->getOwnerid();
  }
}
