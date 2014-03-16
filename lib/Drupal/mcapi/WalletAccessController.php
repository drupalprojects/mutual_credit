<?php

/**
 * @file
 * Contains \Drupal\mcapi\WalletAccessController.
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
//use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Language\Language;


/**
 * Defines an access controller option for the mcapi_wallet entity.
 */
class WalletAccessController extends EntityAccessController {

  private $pluginManager;

  function __construct() {
    $this->pluginManager = \Drupal::service('plugin.manager.mcapi.wallet_access');
  }

  /**
   * {@inheritdoc}
   */
  //@todo define which $ops are possible. I think they will be view, pay & demand
  public function checkAccess(EntityInterface $wallet, $op, $langcode, AccountInterface $account) {
    if ($op == 'edit') {
      //the wallet owner or site manager
      if (($owner = $wallet->getOwner()) && $owner->entityType() == 'user' && $account->id() == $owner->id()) {
        return TRUE;
      }
      else return $account->hasPermission('manage mcapi');
    }


    //TEMP FIX. This means any user can view, edit pay or request from any wallet they share an exchange with
    if ($account->id() == 1) return TRUE;
    return array_intersect_key($wallet->in_exchanges(), referenced_exchanges());

    //something like this will be needed
    return $this->pluginManager
      ->getInstance($wallet->access, $wallet->settings)
      ->check($op, $account);
  }


}
