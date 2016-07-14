<?php

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
class ExchangeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(GroupInterface $exchange, $op, AccountInterface $account) {

    $manager = $exchange->getOwnerId() == $account->id();
    // can't delete undeletable exchanges.
    if ($op == 'delete' && !Exchange::deletable($exchange)) {
      $result = AccessResult::forbidden();
      $result;
    }
    // Site admins can do anything.
    elseif ($account->hasPermission('manage mcapi') || $manager) {
      $result = AccessResult::allowed();
      $result->cachePerUser();
    }
    elseif ($op == 'view') {
      $visib = $exchange->get('visibility')->value;
      if (
        $visib == Exchange::VISIBILITY_TRANSPARENT ||
        ($visib == Exchange::VISIBILITY_RESTRICTED && $account->id()) ||
        //@todo
        ($visib == Exchange::VISIBILITY_PRIVATE && Exchange::hasMember($exchange, User::load($account->id())))
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
