<?php

/**
 * @file
 * Contains \Drupal\mcapi_exchanges\ExchangeAccessControlHandler.
 */

namespace Drupal\mcapi_exchanges;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\user\Entity\User;
use Drupal\mcapi_exchanges\Entity\Exchange;


/**
 * Defines an access controller option for the mcapi_wallet entity.
 */
class ExchangeAccessControlHandler extends EntityAccessControlHandler  {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $exchange, $op, AccountInterface $account) {

    $manager = $exchange->getOwnerId() == $account->id();
    if ($op == 'delete' && !$exchange->deletable($exchange)) {//can't delete undeletable exchanges
      $result = AccessResult::forbidden();
      $result;
    }
    elseif ($account->hasPermission('manage mcapi') || $manager) {//site admins can do anything
      $result = AccessResult::allowed();
      $result->cachePerUser();
    }
    elseif ($op == 'view') {
      $visib = $exchange->get('visibility')->value;
      if (
        $visib == Exchange::VISIBILITY_TRANSPARENT ||
        ($visib == Exchange::VISIBILITY_RESTRICTED && $account->id()) ||
        ($visib == Exchange::VISIBILITY_PRIVATE && $exchange->hasMember(User::load($account->id())))
        ) {
        $result = AccessResult::allowed();
      }
      else {
        $result = AccessResult::forbidden();
      }
      $result->cachePerUser();
    }
    return $result->cacheUntilEntityChanges($exchange);
  }

}
