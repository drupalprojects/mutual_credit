<?php

/**
 * @file
 * Contains \Drupal\mcapi_limits\Plugin\McapiLimitsInterface.
 */

namespace Drupal\mcapi_limits\Plugin;

use \Drupal\mcapi\WalletInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

interface McapiLimitsInterface extends ConfigurablePluginInterface, PluginFormInterface{

  /*
   * get the limits according to the plugin settings
   * 
   * @param WalletInterface $wallet
   *
   * @return array
   *   native currency amounts keyed with 'min' and 'max'
   */
  public function getLimits(WalletInterface $wallet);

}
