<?php

/**
 * @file
 * Contains \Drupal\mcapi_exchanges\Overrides\ExchangesWalletAccessControlHandler.
 */

namespace Drupal\mcapi_exchanges\Overrides;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\mcapi\Exchanges;
use Drupal\Core\Access\AccessResult;

/**
 * Defines an access controller option for the mcapi_wallet entity.
 */
class ExchangesWalletAccessControlHandler extends WalletAccessControlHandler {
  
  
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = [], $return_as_object = FALSE) {
    $access = parent::createAccess($entity_bundle, $account, $context, $return_as_object);
    //if the user is not user 1, and the user is not creating its own, 
    //then the user MUST be in the same exchange
    drupal_set_message("Check that the current user has an exchange in common with ".$entity->label());
    
    return $access;
  }
  /**
   * {@inheritdoc}
   * $ops are details, summary, payin, payout
   */
  public function checkAccess(EntityInterface $entity, $op, $langcode, AccountInterface $account) {
    
    if ($result = $this->initialChecks($entity, $op, $account)) return $result;
    
    $result = parent::checkAccess($entity, $op, $langcode, $account);
    
    if ($result->isNeutral() && $entity->access[$op] == WALLET_ACCESS_EXCHANGE) {
      $user_is_in = Exchanges::in(NULL, TRUE);
      $wallet_is_in = array_keys(Exchanges::walletInExchanges($entity));
      //check that the current user is the same exchange as the wallet
      if (array_intersect($user_is_in, $wallet_is_in)) {
        return $result->allowed()->cachePerUser();
      }
    }
    return $result;
  }
}
