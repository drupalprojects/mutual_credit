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
 *   description = @Translation("Explicit limits"),
 *   settings = {
 *     "min" = "0",
 *     "max" = "0"
 *   }
 * )
 */
class Explicit extends McapiLimitsBase implements McapiLimitsInterface {

  public function settingsForm() {
    $form['minmax'] =  array(
    	'#type' => 'minmax',
      '#default_value' => $this->settings['minmax']
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

  public function getLimits(AccountInterface $account = NULL){
    $limits = array(
      'min' => $this->settings['minmax']['min']['value'],
      'max' => $this->settings['minmax']['max']['value']
    );
    if ($account) {
      //$limits = $this->getPersonal($account) + $limits;
    }
    return $limits;
  }

}
