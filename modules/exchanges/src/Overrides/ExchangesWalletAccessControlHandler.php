<?php

namespace Drupal\mcapi_exchanges\Overrides;

use Drupal\user\Entity\User;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\mcapi_exchanges\Exchanges;
use Drupal\mcapi\Access\WalletAccessControlHandler;

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
    drupal_set_message("Checking that the current user can $op wallet ".$entity->id());
    $result = parent::checkAccess($entity, $op, $account);
    // If the result isn't conclusive then we handle it.
    if ($result->isNeutral()) {
      drupal_set_message("Deciding for special case...");
      $user = User::load($account->id());
      // Grant access if the $account shares an exchange with wallet's owner.
      $user_is_in = Exchanges::memberOf($user);
      $wallet_is_in = Exchanges::memberOf($entity->getOwner());
      // Check that the current user is the same exchange as the wallet.
      if (array_intersect_key($user_is_in, $wallet_is_in)) {
        return $result->allowed()->cachePerUser();
      }
    }
    return $result;
  }

}
