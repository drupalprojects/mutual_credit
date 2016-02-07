<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\TransactionRelative\Payer
 */

namespace Drupal\mcapi\Plugin\TransactionRelative;

use Drupal\mcapi\Plugin\TransactionRelativeInterface;
use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Defines a payee relative to a Transaction entity.
 *
 * @TransactionRelative(
 *   id = "payee",
 *   label = @Translation("Owner of payee wallet"),
 *   description = @Translation("The owner of the payee wallet")
 * )
 */
class Payee extends PluginBase implements TransactionRelativeInterface {//does it go without saying that this implements TransitionInterface

  /**
   * {@inheritdoc}
   */
  public function isRelative(TransactionInterface $transaction, AccountInterface $account) {
    return $transaction->payee->entity->getOwner()->id() == $account->id();
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
    return [$transaction->payee->entity->getOwner()->id()];
  }

}
