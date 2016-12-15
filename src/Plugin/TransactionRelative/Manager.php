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
 *   id = "manager",
 *   label = @Translation("Accounting manager"),
 *   description = @Translation("users with permission to 'manage stuff'")
 * )
 */
class Manager extends PluginBase implements TransactionRelativeInterface {

  /**
   * {@inheritdoc}
   */
  public function isRelative(TransactionInterface $transaction, AccountInterface $account) {
    return $account->hasPermission('manage mcapi');
  }

  /**
   * {@inheritdoc}
   */
  public function entityViewsCondition(AlterableInterface $query, Condition $or_group, $uid) {
    // don't limit the view at all.
  }

  /**
   * {@inheritdoc}
   */
  public function getUsers(TransactionInterface $transaction) {
    // Get all the users with permission to manage the site.
    $roles = user_roles(TRUE, 'manage mcapi');
    // There should be a better way to do this...
    return $this->database->select('user__roles', 'ur')
      ->fields('ur', ['uid'])
      ->condition('roles_target_id', array_keys($roles))
      ->execute()->fetchCol();

  }

}
