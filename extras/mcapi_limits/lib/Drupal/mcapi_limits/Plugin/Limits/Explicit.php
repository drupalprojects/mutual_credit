<?php

/**
 * @file
 *  Contains Drupal\mcapi_limits\Plugin\Limits\Explicit
 *
 */

namespace Drupal\mcapi_limits\Plugin\Limits;

use Drupal\mcapi\TransactionInterface;
//use Drupal\mcapi\CurrencyInterface;
use \Drupal\Core\Config\ConfigFactory;
use \Drupal\mcapi_limits\McapiLimitsBase;
use \Drupal\mcapi_limits\McapiLimitsInterface;
use \Drupal\Core\Session\AccountInterface;

/**
 * No balance limits
 *
 * @Limits(
 *   id = "explicit",
 *   label = @Translation("Explicit"),
 *   description = @Translation("Explicit limits")
 * )
 */
class Explicit extends McapiLimitsBase implements McapiLimitsInterface {

  public function settingsForm() {
    $form['minmax'] =  array(
    	'#type' => 'minmax',
      '#default_value' => $this->currency->limits_settings['minmax']
    );
    $form += parent::settingsForm();
    return $form;
  }

  public function checkPayer(AccountInterface $account, $diff) {
    $limits = $this->getLimits($account);
    return TRUE;
  }
  public function checkPayee(AccountInterface $account, $diff) {
    $limits = $this->getLimits($account);
    return TRUE;
  }

  public function getBaseLimits(AccountInterface $account) {
    $limits = array(
      'min' => $this->currency->limits_settings['minmax']['min']['value'],
      'max' => $this->currency->limits_settings['minmax']['max']['value']
    );
    return $limits;
  }

  public function view(AccountInterface $account) {
    return array(
      '#theme' => 'mcapi_limits',
      '#account' => $account,
      '#currency' => $this->currency,
    );
  }

}
