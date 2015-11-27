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
  public function condition(QueryInterface $query) {

  }


  /**
   * {@inheritdoc}
   */
  public function getUsers(TransactionInterface $transaction) {
    //get all the users with permission to manage the site
    $roles = user_roles(TRUE, 'manage mcapi');
    //there should be a better way to do this...
    return $this->database->select('user__roles', 'ur')
      ->fields('ur', ['uid'])
      ->condition('roles_target_id', array_keys($roles))
      ->execute()->fetchCol();

  }

}
