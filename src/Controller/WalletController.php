<?php

/**
 * @file
 * Contains \Drupal\mcapi\Controller\WalletController.
 * @todo this might be deprecated @see \Drupal\mcapi\Entity\WalletRouteProvider
 */

namespace Drupal\mcapi\Controller;

use Drupal\Core\Entity\EntityInterface;

/**
 * Returns all the wallets held by the given entity, in summary view mode
 * @note Only applicable when mcapi.settings config value wallet_tab is TRUE
 */
class WalletController {

  /**
   * router title callback
   *
   * @param EntityInterface $entity
   *
   * @return string
   */
  public function entityWalletsTitle(EntityInterface $entity) {
    return $wallet->label();
  }

  /**
   * router callback
   * Show all an entities wallets in summary mode
   *
   * @param EntityInterface $entity
   *
   * @return array
   *   a renderable array
   */
  function entityWallets(EntityInterface $entity) {
    die('WalletController::entityWallets'); //yet to be developed or deleted
  }

}
