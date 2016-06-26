<?php

namespace Drupal\mcapi_forms;

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
    $mode = $route_match->getRouteObject()->getOptions()['parameters']['mode'];
    $settings = EntityFormDisplay::load('mcapi_transaction.mcapi_transaction.' . $mode)->getThirdPartySettings('mcapi_forms');
    if (empty($settings['direction'])) {
      return AccessResult::allowedIfHasPermission($account, 'create 3rdparty transactions');
    }
    else {
      return AccessResult::allowed();
    }
  }

}
