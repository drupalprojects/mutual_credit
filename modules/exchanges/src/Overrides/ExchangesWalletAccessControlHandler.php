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
      drupal_set_message('@todo rewrite ExchangesWalletAccessControlHandler');
      $user = User::load($account->id());
      if ($exchange_membership = group_exclusive_membership_get('exchange', $user) and $wallet_membership = group_exclusive_membership_get('exchange', $entity)) {
        $exchange = $exchange_membership->getGroup();
        if ($exchange->id() == $wallet_membership->getGroup()->id()) {
          switch ($op) {
            case 'view':
              // View if the $account is in the same exchange as the wallet.
              $result = AccessResult::allowed();
              break;
            case 'update':
              // Update if the $account is the wallet's owner or the account has permission to edit all
              $result = GroupAccessResult::allowedIfHasGroupPermission($exchange, 'manage transactions')
                ->orIf(AccessResult::allowedIf($account->id() == $entity->getownerId()))
                ->orIf(AccessResult::allowedIfHasPermission('manaage mcapi'));
              break;
            case 'delete':
              if (!$entity->isVirgin())  {
                $result = AccessResult::forbidden();
              }
              // Can only delete wallet if there are no transactions.
              else {
                $result = GroupAccessResult::allowedIfHasGroupPermission($exchange, 'manage transactions')
                  ->orIf(AccessResult::allowedIf($account->id() == $entity->getownerId()))
                  ->orIf(AccessResult::allowedIfHasPermission('manaage mcapi'));
              }
          }
        }
      }
    }
    return $result->cachePerUser();
  }

}
