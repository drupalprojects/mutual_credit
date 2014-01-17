<?php

/**
 * @file
 * Contains \Drupal\simple_access\WalletAccessController.
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;


/**
 * Defines an access controller option for the mcapi_wallet entity.
 */
class TransactionAccessController extends EntityAccessController {

  private $pluginManager;

  function __construct() {
    $this->pluginManager = \Drupal::service('plugin.manager.mcapi.wallet_access');
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $wallet, $op, $langcode, AccountInterface $account) {
    die('ddd');
    return $this->pluginManager
      ->getInstance($wallet->access, $wallet->settings)
      ->check($op, $account);
  }


}
