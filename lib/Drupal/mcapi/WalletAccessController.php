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
  public function checkAccess(EntityInterface $wallet, $op, $langcode, AccountInterface $account) {
    return TRUE;
    //remember we're only interested in view access but not for router callbacks because the wallet has no uri
    //although perhaps it should - like bitcoin!
    return $this->pluginManager
      ->getInstance($wallet->access, $wallet->settings)
      ->check($op, $account);
  }


}
