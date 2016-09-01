<?php

namespace Drupal\mcapi_exchanges;

use Drupal\mcapi_exchanges\Exchanges;
use Drupal\mcapi\Entity\Wallet;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\user\Entity\User;

/**
 * Defines an access controller option for the mcapi_wallet entity.
 */
class ExchangeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(GroupInterface $exchange, $op, AccountInterface $account) {

    // Only exchanges with no members wallets, and no intertrading transactions
    // can be deleted.
    if ($op == 'delete') {
      $wid = Exchanges::getIntertradingWalletId($exchange);
      if (count(Wallet::load($wid)->history()) || Mcapi::walletsOf($exchange)) {
        $result = AccessResult::forbidden();
      }
      else {
        $result = AccessResult::allowed();
      }
      // Hard to cache this
      return $result;
    }

    // Site admins can do anything.
    if ($account->hasPermission('manage mcapi') || $exchange->getOwnerId() == $account->id()) {
      $result = AccessResult::allowed();
    }
    elseif(0) {
      // @todo the $account has admin permission of the group.
      // @note the api function isn't in the group module yet (Sept '16)
    }
    elseif ($op == 'view') {
      $visib = $exchange->get('visibility')->value;
      switch($exchange->get('visibility')->value) {
        case Exchange::VISIBILITY_TRANSPARENT:
          $result = AccessResult::allowed();
          break;
        case Exchange::VISIBILITY_RESTRICTED:
          $result = AccessResult::allowedIf($account->isAuthenticated());
          break;
        case Exchange::VISIBILITY_PRIVATE:
          //allow if the theuser is a member of the given group.
          $mem = \Drupal::service('group.membership_loader')->load($exchange, User::load($account->id()));
          $result = AccessResult::allowedIf((bool)$mem);
          break;
        default:
          $result = AccessResult::forbidden();
      }
    }
    return $result->cachePerUser()->cacheUntilEntityChanges($exchange);
  }

}
