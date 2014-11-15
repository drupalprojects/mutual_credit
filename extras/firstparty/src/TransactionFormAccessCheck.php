<?php

/**
 * @file
 * Contains \Drupal\mcapi_1stparty\TransactionFormAccessCheck.
 * @todo deprecate this in favour of 'use' op on the Designed form entity
 */

namespace Drupal\mcapi_1stparty;

use Symfony\Component\Routing\Route;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessCheck;
use Drupal\Core\Entity\EntityType;
use Drupal\user\Entity\User;
use Drupal\mcapi\Entity\Wallet;
use Drupal\mcapi_1stparty\Entity\FirstPartyFormDesign;


/**
 * Forms which designate certain exchanges can only be accessed in those exchanges
 */
class TransactionFormAccessCheck extends EntityAccessCheck {

  /**
   * The transaction form can only be visited if it is in all exchanges
   * or the user is in the exchange the the form designates.
   *
   * @return AccessResultInterface
   *
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $result = AccessResult::forbidden();//TODO when to invalidate this?
    $user = User::load($account->id());
    if ($wids = \Drupal::entityManager()->getStorage('mcapi_wallet')->getOwnedIds($user)) {
      $editform = FirstPartyFormDesign::load($route->getOption('parameters')['1stparty_editform']);
      //TODO the caching
      if ($account->hasPermission('configure mcapi')) {
        $result = AccessResult::allowed();
      }
      //TODO how do we get $editform? //do we need a 'custom' access controller?
      elseif ($exchange_id = $editform->get('exchange')) {
        $exchange = Exchange::load($exchange_id);
        if (is_object($exchange) && $exchange->is_member($account)) {
          $result = AccessResult::allowed();
        }
      }
      //user can access the form if they are in at least one exchange
      elseif (count(Exchange::referenced_exchanges($account, TRUE)) != 0) {
        $result = AccessResult::allowed();
      }
    }
    return $result;
  }
}

