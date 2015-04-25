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
 * Defines a payee relative to a Transaction entity.
 *
 * @TransactionRelative(
 *   id = "payee",
 *   label = @Translation("Payee"),
 *   description = @Translation("The owner of the payee wallet")
 * )
 */
class Payee extends PluginBase implements TransactionRelativeInterface {//does it go without saying that this implements TransitionInterface

  /**
   * {@inheritdoc}
   */
  public function isRelative(TransactionInterface $transaction, AccountInterface $account) {
    return $transaction->payee->entity->ownerUserId() == $account->id();
  }

  /**
   * {@inheritdoc}
   */
  public function condition(QueryInterface $query) {

  }
}
