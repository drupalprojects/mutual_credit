<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\TransactionRelative\Payer
 */

namespace Drupal\mcapi\Plugin\TransactionRelative;

use Drupal\mcapi\Plugin\TransactionRelativeInterface;
use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Database\Query\AlterableInterface;

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
  public function indexViewsCondition(AlterableInterface $query, $or_group, $uid) {
    $query->join('mcapi_wallet', 'u1_wallet', 'mcapi_transactions_index.wallet_id = u1_wallet.wid AND mcapi_transactions_index.outgoing = 0');
    $query->join('users', 'u1_user', "u1_wallet.holder_entity_type = 'user' AND u1_wallet.holder_entity_id = u1_user.uid");
    $or_group->condition('u1_user.uid', $uid);
  }

  /**
   * {@inheritdoc}
   */
  public function entityViewsCondition(AlterableInterface $query, $or_group, $uid) {
    $query->join('mcapi_wallet', 'payee_wallet', 'base_table.payee = payee_wallet.wid');
    $query->join('users', 'payee_user', "payee_wallet.holder_entity_type = 'user' AND payee_wallet.holder_entity_id = payee_user.uid");
    $or_group->condition('payee_user.uid', $uid);
  }

  /**
   * {@inheritdoc}
   */
  public function getUsers(TransactionInterface $transaction) {
    return [$transaction->payee->entity->getOwner()->id()];
  }

}
