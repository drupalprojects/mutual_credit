<?php

namespace Drupal\mcapi_forms;

use Drupal\mcapi\Entity\Wallet;
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
    $config_name = 'mcapi_transaction.mcapi_transaction.' . $mode;
    $settings = EntityFormDisplay::load($config_name)->getThirdPartySettings('mcapi_forms');

    if (empty($settings['direction'])) {
      return AccessResult::allowedIfHasPermission($account, 'create 3rdparty transaction');
    }
    else {
      //we can access this form if we have one or wallets which operate in the right direction.
      $query = \Drupal::entityQuery('mcapi_wallet')
      // Not intertrading wallets.
        ->condition('payways', Wallet::PAYWAY_AUTO, '<>')
        ->condition('orphaned', 0)
        ->condition('holder_entity_type', 'user')
        ->condition('holder_entity_id', $account->id());
      $direction = [Wallet::PAYWAY_ANYONE_BI];
      if ($settings['direction'] == 1) {
        $direction[] = Wallet::PAYWAY_ANYONE_IN;
      }
      else {
        $direction[] = Wallet::PAYWAY_ANYONE_OUT;
      }
      $query->condition('payways', $direction, 'IN');

      return AccessResult::allowedIf($query->execute());
    }
  }

}
