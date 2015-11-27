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
 * Defines a payer relative to a Transaction entity.
 *
 * @TransactionRelative(
 *   id = "payer",
 *   label = @Translation("Payer"),
 *   description = @Translation("The owner of the payer wallet")
 * )
 */
class Payer extends PluginBase implements TransactionRelativeInterface {

  /**
   * {@inheritdoc}
   */
  public function isRelative(TransactionInterface $transaction, AccountInterface $account) {
    return $transaction->payer->entity->getOwner()->id() == $account->id();
  }

  /**
   * {@inheritdoc}
   */
  public function condition(QueryInterface $query) {

  }

  /**
   * {@inheritdoc}
   */
  public function access(){}


  /**
   * {@inheritdoc}
   */
  public function getUsers(TransactionInterface $transaction) {
    return [$transaction->payer->entity->getOwner()->id()];
  }


}
