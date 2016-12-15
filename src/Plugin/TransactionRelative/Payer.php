<?php

namespace Drupal\mcapi\Plugin\TransactionRelative;

use Drupal\mcapi\Plugin\TransactionRelativeInterface;
use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Database\Query\Condition;

/**
 * Defines a payer relative to a Transaction entity.
 *
 * @TransactionRelative(
 *   id = "payer",
 *   label = @Translation("Owner of payer wallet"),
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
  public function entityViewsCondition(AlterableInterface $query, Condition $or_group, $uid) {
    $query->join('mcapi_wallet', 'payer_wallet', 'base_table.payer = payer_wallet.wid');
    $query->join('users', 'payer_user', "payer_wallet.holder_entity_type = 'user' AND payer_wallet.holder_entity_id = payer_user.uid");
    $or_group->condition('payer_user.uid', $uid);
  }

  /**
   * {@inheritdoc}
   */
  public function getUsers(TransactionInterface $transaction) {
    return [$transaction->payer->entity->getOwner()->id()];
  }

}
