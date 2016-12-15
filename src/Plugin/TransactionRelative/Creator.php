<?php

namespace Drupal\mcapi\Plugin\TransactionRelative;

use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\mcapi\Plugin\TransactionRelativeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Database\Query\Condition;

/**
 * Defines a creator relative to a Transaction entity.
 *
 * @TransactionRelative(
 *   id = "creator",
 *   label = @Translation("Creator"),
 *   description = @Translation("The user who created the transaction")
 * )
 */
class Creator extends PluginBase implements TransactionRelativeInterface {

  /**
   * {@inheritdoc}
   */
  public function isRelative(TransactionInterface $transaction, AccountInterface $account) {
    return $transaction->creator->entity->id() == $account->id();
  }

  /**
   * {@inheritdoc}
   */
  public function entityViewsCondition(AlterableInterface $query, Condition $or_group, $uid) {
    $or_group->condition('base_table.creator', $uid);
  }

  /**
   * {@inheritdoc}
   */
  public function getUsers(TransactionInterface $transaction) {
    return [$transaction->getOwnerId()];
  }

}
