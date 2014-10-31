<?php

/**
 * @file
 * Contains \Drupal\mcapi_exchanges\ExchangeAccessControlHandler.
 */

namespace Drupal\mcapi_exchanges;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\Language;
use Drupal\mcapi\ExchangeInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Access\AccessResult;


/**
 * Defines an access controller option for the mcapi_wallet entity.
 */
class ExchangeAccessControlHandler extends EntityAccessControlHandler  {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $exchange, $op, $langcode, AccountInterface $account) {

    $manager = $exchange->getOwnerId() == $account->id();
    if ($op == 'delete' && !$exchange->deletable($exchange)) {//can't delete undeletable exchanges
      $result = AccessResult::forbidden();
      $result->cacheUntilEntityChanges($exchange);
    }
    elseif ($account->hasPermission('manage mcapi') || $manager) {//site admins can do anything
      $result = AccessResult::allowed();
      $result->cachePerUser();
    }
    elseif ($op == 'view') {
      $visib = $exchange->get('visibility')->value;
      if (
        $visib == 'public' ||
        ($visib == 'restricted' && $account->id()) ||
        ($visib == 'private' && $exchange->is_member(User::load($account->id())))
        ) {
        $result = AccessResult::allowed();
      }
      else {
        $result =  AccessResult::forbidden();
      }
      $result->cachePerUser();
    }
    return $result;
  }

}
