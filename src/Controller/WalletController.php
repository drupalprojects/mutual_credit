<?php

namespace Drupal\mcapi\Controller;

use Drupal\mcapi\Storage\WalletStorage;
use Drupal\Core\Controller\ControllerBase;

/**
 * Returns all the wallets held by the given entity, in summary view mode.
 *
 * @note Only applicable when mcapi.settings config value wallet_tab is TRUE
 *
 * @todo inject $routeMatch
 */
class WalletController extends ControllerBase {

  public function getHolder() {
    $params = \Drupal::routeMatch()->getParameters()->all();
    list($entity_type_id, $entity_id) = each($params);
    return $this->entityTypeManager()->getStorage($entity_type_id)->load($entity_id);
  }

  /**
   * Get the name of the wallet(s) holder.
   *
   * @return string
   *   The entity label of the wallet holder.
   */
  public function entityWalletsTitle() {
    return $this->getHolder()->label();
  }

  /**
   * Router callback.
   *
   * Show all an entities wallets in summary mode.
   * this is rather tricky because we don't know what arguments would be passed
   * from the url, so we have to load them from scratch.
   *
   * @return array
   *   A renderable array.
   */
  public function entityWallets() {
    $wallets = WalletStorage::walletsOf($this->getHolder(), TRUE);
    return $this->entityTypeManager()->getViewBuilder('mcapi_wallet')->viewMultiple($wallets);
  }

}
