<?php

/**
 * @file
 * Contains \Drupal\mcapi\Controller\WalletController.
 * @deprecated
 * @see \Drupal\mcapi\Entity\WalletRouteProvider
 */

namespace Drupal\mcapi\Controller;

use Drupal\Core\Entity\EntityInterface;

/**
 * Returns all the wallets held by the given entity, in summary view mode
 * @note Only applicable when mcapi.settings config value wallet_tab is TRUE
 */
class WalletController {

  function __construct() {
    $params = \Drupal::routeMatch()->getParameters()->all();
    list($entity_type_id, $entity_id) = each($params);
    $this->holder = \Drupal::entityTypeManager()->getStorage($entity_type_id)->load($entity_id);
  }

  /**
   * router title callback
   *
   * @param EntityInterface $entity
   *
   * @return string
   */
  public function entityWalletsTitle() {
    return $this->holder->label();
  }

  /**
   * router callback
   * Show all an entities wallets in summary mode
   * this is rather tricky because we don't know what arguments would be passed
   * from the url, so we have to load them from scratch
   *
   * @param EntityInterface $entity
   *
   * @return array
   *   a renderable array
   */
  function entityWallets() {

    $wallets = \Drupal\mcapi\Mcapi::walletsOf($this->holder, TRUE);
    return \Drupal::entityTypeManager()->getViewBuilder('mcapi_wallet')->viewMultiple($wallets);
  }

}
