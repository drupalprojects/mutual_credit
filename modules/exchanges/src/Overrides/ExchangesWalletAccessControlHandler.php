<?php

namespace Drupal\mcapi_exchanges\Overrides;

use Drupal\mcapi\Access\WalletAccessControlHandler;
use Drupal\group\Access\GroupAccessResult;
use Drupal\user\Entity\User;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access controller option for the mcapi_wallet entity.
 * @todo lots more work.
 */
class ExchangesWalletAccessControlHandler extends WalletAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = [], $return_as_object = FALSE) {
    $access = parent::createAccess($entity_bundle, $account, $context, $return_as_object);
    // If the user is not user 1, and the user is not creating its own,
    // then the current user MUST be in the same exchange
    if (\Drupal::currentUser()->id() != $account->id()) {
      drupal_set_message("Check that the current user has an exchange in common with ".$account->getDisplayName());
    }
    return $access;
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $op, AccountInterface $account) {
    $result = parent::checkAccess($entity, $op, $account);
    // If the result isn't conclusive then we handle it.
    if ($result->isNeutral()) {
      // Check that the current user is the same exchange as the wallet.
      // @todo rewrite this when wallets are in exchanges directly
      drupal_set_message('@todo rewrite ExchangesWalletAccessControlHandler');
      $user = User::load($account->id());
      $exchange_membership = group_exclusive_membership_get('exchange', $user);
      if (!$exchange_membership) {
        $result = AccessResult::neutral();
      }
      $owner_membership = group_exclusive_membership_get('exchange', $entity->getOwner());
      if ($exchange_membership->getGroup()->id() == $owner_membership->getGroup()->id()) {
        switch ($op) {
          case 'view':
            // Grant access if the $account shares an exchange with wallet's owner.
            $result = AccessResult::allowed();
            break;
          case 'update':
            // Grant access if the $account has permission to edit all
            $result = GroupAccessResult::allowedIfHasGroupPermission($exchange_membership->getGroup(), 'manage transactions');
            break;
          case 'delete':
            // Can only delete wallet if there are no transactions.
            $result = AccessResult::allowedIf($entity->isVirgin());
        }
      }
    }
    return $result->cachePerUser();
  }

}
