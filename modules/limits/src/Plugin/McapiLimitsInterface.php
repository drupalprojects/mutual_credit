<?php

namespace Drupal\mcapi_limits\Plugin;

use \Drupal\mcapi\Entity\WalletInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Interface for Limits object.
 */
interface McapiLimitsInterface extends ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Get the limits according to the plugin settings.
   *
   * @param WalletInterface $wallet
   *   The wallet.
   *
   * @return array
   *   Native currency amounts keyed with 'min' and 'max'.
   */
  public function getLimits(WalletInterface $wallet);

}
