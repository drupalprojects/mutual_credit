<?php

/**
 * @file
 * Contains \Drupal\mcapi_exchanges\Overrides\ExchangesWalletAccessControlHandler.
 */

namespace Drupal\mcapi_exchanges\Overrides;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\mcapi_exchanges\Exchanges;
use Drupal\mcapi\Access\WalletAccessControlHandler;

/**
 * Defines an access controller option for the mcapi_wallet entity.
 */
class ExchangesWalletAccessControlHandler extends WalletAccessControlHandler {
  
  /**
   * {@inheritdoc}
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = [], $return_as_object = FALSE) {
    $access = parent::createAccess($entity_bundle, $account, $context, $return_as_object);
    //if the user is not user 1, and the user is not creating its own, 
    //then the current user MUST be in the same exchange
    //drupal_set_message("Check that the current user has an exchange in common with ".$entity->label());
    return $access;
  }
  
  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $op, AccountInterface $account) {
    
    $result = parent::checkAccess($entity, $op, $account);
    //if the result isn't conclusive then we handle it
    if ($result->isNeutral()) {
      $user = \Drupal\user\Entity\User::load($account->id());
      //grant access if the $account shares an exchange with wallet's owner
      $user_is_in = Exchanges::memberOf($user, TRUE);
      $wallet_is_in = Exchanges::memberOf($entity->getOwner(), TRUE);
      //check that the current user is the same exchange as the wallet
      if (array_intersect_key($user_is_in, $wallet_is_in)) {
        return $result->allowed()->cachePerUser();
      }
    }
    return $result;
  }
}
