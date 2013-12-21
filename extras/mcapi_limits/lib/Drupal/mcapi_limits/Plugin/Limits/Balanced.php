<?php

/**
 * @file
 *  Contains Drupal\mcapi_limits\Plugin\Limits\Balanced
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
 *   id = "balanced",
 *   label = @Translation("Balanced"),
 *   description = @Translation("Limits are the same distance from zero")
 * )
 */
class Balanced extends McapiLimitsBase implements McapiLimitsInterface {

  public function settingsForm() {
    $form['liquidity'] =  $this->widget($this->currency->limits_settings['liquidity']);
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

  public function getBaseLimits(AccountInterface $account){
    $val = $this->currency->limits_settings['liquidity']['value'];
    $limits = array(
      'min' => -$val,
      'max' => $val
    );
    return $limits;
  }

  public function view(AccountInterface $account) {
    return array(
      '#theme' => 'mcapi_limits_balanced',
      '#account' => $account,
      '#currency' => $this->currency,
      '#size' => '100%'
    );
  }

  public function widget($default) {
    return array(
      '#title' => t('Liquidity per user'),
      '#description' => t('The distance from zero a user can trade'),
      '#type' => 'worth',
      '#currcodes' => array($this->currency->id()),
      '#default_value' => $default,
      '#min' => 0,
    );
  }

}
