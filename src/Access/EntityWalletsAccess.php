<?php

/**
 * @file
 * Contains \Drupal\mcapi\Access\EntityWalletsAccess.
 * Custom Access control handler to view an entity's wallets
 */

namespace Drupal\mcapi\Access;

use Drupal\mcapi\Mcapi;
use Drupal\mcapi\Entity\Wallet;
use Symfony\Component\Routing\Route;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessCheck;

class EntityWalletsAccess extends EntityAccessCheck {

  /**
   * find out whether the wallets held by an entity can be viewed
   *
   * @return AccessResultInterface
   */
  public function view(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {

    $result = AccessResult::allowed()->cachePerUser();
    if ($account->hasPermission('manage mcapi') || $account->hasPermission('view all wallets')) {
      return $result;
    }
    list($entity_type_id, $entity_id) = each($route_match->getParameters()->all());

    //can view the wallets if the currency user is the holder.
    if ($entity_type_id == 'user' && $entity_id == $account->id()) {
      return $result;
    }

    $holder = \Drupal::entityTypeManager()->getStorage($entity_type_id)->load($entity_id);
    if ($entity_id == $holder->getOwnerId()) {
      return $result;
    }

    return $result->forbidden();
  }
}
/*
      return AccessResult::allowedIfhasPermission($account, 'view all transactions')
        ->orif(
          AccessResult::allowedIf($entity->getOwnerId() == $account->id())
        )
        ->cachePerUser();
 *
 */