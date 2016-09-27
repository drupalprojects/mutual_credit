<?php

namespace Drupal\mcapi_forms;

use Drupal\mcapi\Mcapi;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\Routing\Route;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityAccessCheck;

/**
 * Check access for transaction entity.
 */
class TransactionFormAccessCheck extends EntityAccessCheck {

  private $walletQuery;

  function __construct() {
    $this->walletQuery = \Drupal::entityQuery('mcapi_wallet');
  }

  /**
   * Implement access control specific to transaction forms.
   *
   * @return AccessResultInterface
   *   Normal access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    // This complements to TransactionAccessControlHandler::checkCreateAccess().
    // For some reason neutral is interpreted as forbidden when merged with
    // allowed so though I wanted this to be neutral coz its not used yet, it is
    // allowed for now.

    // Is the user allowed to pay in and out of at least 1 wallet?
    if (!Mcapi::enoughWallets($account->id())) {
      return AccessResult::forbidden()->cachePerUser();
    }

    $mode = $route_match->getRouteObject()->getOptions()['parameters']['mode'];
    $config_name = 'mcapi_transaction.mcapi_transaction.' . $mode;
    $dir = EntityFormDisplay::load($config_name)->getThirdPartySettings('mcapi_forms')['direction'];
    if (empty($dir)) {
      return AccessResult::allowedIfHasPermission($account, 'create 3rdparty transaction')->cachePerUser();
    }
    else {
      // Access is granted if there are any wallets going the right direction;
      $wids = mcapi_forms_access_direction($account->id(), $dir);
      return AccessResult::allowedIf(!empty($wids))->cachePerUser();
    }
  }

}
