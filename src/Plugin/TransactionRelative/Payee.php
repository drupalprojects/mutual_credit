<?php

namespace Drupal\mcapi\Plugin\TransactionRelative;

use Drupal\mcapi\Plugin\TransactionRelativeInterface;
use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Database\Query\Condition;

/**
 * Defines a payee relative to a Transaction entity.
 *
 * @TransactionRelative(
 *   id = "payee",
 *   label = @Translation("Owner of payee wallet"),
 *   description = @Translation("The owner of the payee wallet")
 * )
 */
class Payee extends PluginBase implements TransactionRelativeInterface {

  /**
   * {@inheritdoc}
   */
  public function isRelative(TransactionInterface $transaction, AccountInterface $account) {
    return $transaction->payee->entity->getOwner()->id() == $account->id();
  }

  /**
   * {@inheritdoc}
   */
  public function entityViewsCondition(AlterableInterface $query, Condition $or_group, $uid) {
    $query->join('mcapi_wallet', 'payee_wallet', 'base_table.payee = payee_wallet.wid');
    $query->join('users', 'payee_user', "payee_wallet.holder_entity_id = payee_user.uid AND payee_wallet.holder_entity_type = 'user'");
    $or_group->condition('payee_user.uid', $uid);
  }

  /**
   * {@inheritdoc}
   */
  public function getUsers(TransactionInterface $transaction) {
    return [$transaction->payee->entity->getOwner()->id()];
  }

}
