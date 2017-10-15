<?php

namespace Drupal\mcapi\Access;

use Symfony\Component\Routing\Route;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessCheck;

/**
 * Entity access controller for Wallet entity.
 */
class EntityWalletsAccess extends EntityAccessCheck {

  /**
   * {@inheritdoc}
   */
  public function view(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {

    $result = AccessResult::allowed()->cachePerUser();
    if ($account->hasPermission('manage mcapi') || $account->hasPermission('view all wallets')) {
      return $result;
    }
    
    // Can view the wallets if the currency user is the holder.
    list($entity_type_id, $entity_id) = each($route_match->getParameters()->all());
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
