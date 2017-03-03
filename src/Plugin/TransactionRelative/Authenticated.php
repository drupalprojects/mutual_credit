<?php

namespace Drupal\mcapi\Plugin\TransactionRelative;

use Drupal\mcapi\Plugin\TransactionRelativeInterface;
use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Database\Query\Condition;

/**
 * Defines a payer relative to a Transaction entity.
 *
 * @TransactionRelative(
 *   id = "authenticated",
 *   label = @Translation("Authenticated users"),
 *   description = @Translation("Anyone who is logged in"),
 *   weight = -1
 * )
 */
class Authenticated extends PluginBase implements TransactionRelativeInterface {

  /**
   * {@inheritdoc}
   */
  public function isRelative(TransactionInterface $transaction, AccountInterface $account) {
    return !empty($account->isAuthenticated());
  }

  /**
   * {@inheritdoc}
   */
  public function entityViewsCondition(AlterableInterface $query, Condition $or_group, $uid) {
    if (!$uid) {
      $query->condition('1', 0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getUsers(TransactionInterface $transaction) {
    return $this->database->select('users_field_data', 'd')
      ->fields('d', ['uid'])
      ->condition('status', 1)
      ->execute()->fetchCol();
  }

}
