<?php

/**
 * @file
 * Contains \Drupal\mcapi\Access\WalletAccessController.
 */

namespace Drupal\mcapi\Access;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access controller option for the mcapi_wallet entity.
 */
class WalletAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   * $ops are list, summary, pay, charge
   */
  public function checkAccess(EntityInterface $entity, $op, $langcode, AccountInterface $account) {
    //disabled for testing
    if ($account->hasPermission('manage mcapi')) return TRUE;
    //edit isn't a configurable operation. Only the owner can do it
    if ($op == 'edit') {
      $entity->access['edit'] = array($entity->user_id());
    }
    if (is_array($entity->access[$op])) {//designated users
      return in_array($account->id(), $entity->access[$op]);
    }
    switch ($entity->access[$op]) {
    	case WALLET_ACCESS_EXCHANGE:
    	  return array_intersect_key($entity->in_exchanges(), referenced_exchanges(NULL, TRUE));
    	case WALLET_ACCESS_AUTH:
    	  return $account->id();
    	case WALLET_ACCESS_ANY:
    	  return TRUE;
    	default:
    	  throw new \Exception('WalletAccessController::checkAccess() does not know op: '.$op);
    }
  }

}
