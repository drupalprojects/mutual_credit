<?php

namespace Drupal\mcapi_exchanges\Overrides;

use Drupal\mcapi\Access\TransactionAccessControlHandler;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Overrides the access controller option for the mcapi_transaction entity.
 */
class ExchangeTransactionAccessControlHandler extends TransactionAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $result = parent::checkCreateAccess($account, $context, $entity_bundle);
    if (!$result->isForbidden()) {
      $mems = \Drupal::service('group.membership_loader')->loadByUser($account);
      foreach ($mems as $membership) {
        $group = $membership->getGroup();
        if ($group->getGroupType()->id() == 'exchange') {
          // @todo check if the exchange is active and has a currency
          if ($group->currencies->count()) {
            $result = AccessResult::allowed()->cachePerUser();
          }
        }
      }
    }
    return $result;
  }

}
