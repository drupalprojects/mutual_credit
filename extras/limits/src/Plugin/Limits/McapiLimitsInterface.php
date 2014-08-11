<?php

/**
 * @file
 * Contains \Drupal\mcapi_limits\Plugin\Limits\McapiLimitsInterface.
 */

namespace Drupal\mcapi_limits\Plugin\Limits;

use \Drupal\mcapi\Entity\WalletInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

interface McapiLimitsInterface extends ConfigurablePluginInterface, PluginFormInterface{

  /*
   * get the limits according to the plugin settings
   * @param WalletInterface $wallet
   *
   * @return array
   *   native currency amounts keyed with 'min' and 'max'
   */
  public function getLimits(WalletInterface $wallet);


  //NB this function receives an extra hidden argument, mcapiCurrency $currency
  //public function buildConfigurationForm(array $form, $form_state);

}
