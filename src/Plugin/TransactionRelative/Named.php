<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\TransactionRelative\Named
 */

namespace Drupal\mcapi\Plugin\TransactionRelative;

use Drupal\mcapi\Plugin\TransactionRelativeInterface;
use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Defines a nominated user relative to wallets in a transaction.
 *
 * @TransactionRelative(
 *   id = "named",
 *   label = @Translation("Named wallet user"),
 *   description = @Translation("A user nominated to pay in to the payer wallet or payout of the payee wallet")
 * )
 */
class Named extends PluginBase implements TransactionRelativeInterface {

  /**
   * {@inheritdoc}
   */
  public function isRelative(TransactionInterface $transaction, AccountInterface $account) {
    $targets = getUsers($transaction);
    dsm($targets);
    $id = $account->id();
    foreach ($targets as $target) {
      if ($id == $target['target_id']) return TRUE;
    }
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
    return array_merge(
      $transaction->payer->entity->getValue('payers'),
      $transaction->payee->entity->getValue('payees')
    );
  }



}
