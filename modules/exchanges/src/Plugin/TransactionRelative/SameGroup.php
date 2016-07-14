<?php

namespace Drupal\mcapi_exchanges\Plugin\TransactionRelative;

use Drupal\mcapi\Plugin\TransactionRelativeInterface;
use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\group\Entity\GroupContent;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Database\Query\Condition;

/**
 * Defines a payer relative to a Transaction entity.
 *
 * @TransactionRelative(
 *   id = "same_group",
 *   label = @Translation("Group"),
 *   description = @Translation("Is in the same group as this transaction")
 * )
 *
 * @todo this isn't tested.
 */
class SameGroup extends PluginBase implements TransactionRelativeInterface {

  /**
   * {@inheritdoc}
   */
  public function isRelative(TransactionInterface $transaction, AccountInterface $account) {
    $my_memberships = \Drupal::service('group.membership_loader')->loadByUser($account);
    foreach ($my_memberships as $ship) {
      $my_gids[] = $ship->getGroup()->id();
    }
    $memberships = GroupContent::loadByEntity($transaction);
    foreach ($memberships as $ship) {
      if (in_array($ship->getGroup()->id(), $my_gids)) {
        return TRUE;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function indexViewsCondition(AlterableInterface $query, Condition $or_group, $uid) {
    // Get the groups of user $uid
    $account = \Drupal\user\Entity\User::load($uid);
    $user_member_of = \Drupal::service('group.membership_loader')->loadByUser($account);
    foreach ($user_member_of as $membership) {
      $gids[] = $membership->getGroup()->id();
    }
    $query->join(
      'group_content_field_data',
      'gc',
      'gc.type = exchange-group_transactions AND gc.entity_id = mcapi_transactions_index.xid'
    );

    $or_group->condition('gc.gid',  $gids, 'IN');
  }

  /**
   * {@inheritdoc}
   */
  public function entityViewsCondition(AlterableInterface $query, Condition $or_group, $uid) {
    // Get the groups of user $uid
    $account = \Drupal\user\Entity\User::load($uid);
    $user_member_of = \Drupal::service('group.membership_loader')->loadByUser($account);
    foreach ($user_member_of as $membership) {
      $gids[] = $membership->getGroup()->id();
    }
    $query->join(
      'group_content_field_data',
      'gc',
      'gc.type = exchange-group_transactions AND gc.entity_id = mcapi_transaction.xid'
    );
    $or_group->condition('gc.gid',  $gids, 'IN');
  }

  /**
   * {@inheritdoc}
   */
  public function getUsers(TransactionInterface $transaction) {
    return [$transaction->payer->entity->getOwner()->id()];
  }

}
