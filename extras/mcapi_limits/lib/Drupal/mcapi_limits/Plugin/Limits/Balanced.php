<?php

/**
 * @file
 *  Contains Drupal\mcapi_limits\Plugin\Limits\Balanced
 *
 */

namespace Drupal\mcapi_limits\Plugin\Limits;

use \Drupal\mcapi_limits\McapiLimitsBase;
use \Drupal\mcapi_limits\McapiLimitsInterface;
use \Drupal\Core\Entity\EntityInterface;


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

  /**
   * @see \Drupal\mcapi_limits\McapiLimitsBase::settingsForm()
   */
  public function settingsForm() {
    $form['liquidity'] =  $this->widget($this->limits_settings['liquidity']);
    print_r($form['liquidity']);
    $form += parent::settingsForm();
    return $form;
  }

  /**
   * (non-PHPdoc)
   * @see \Drupal\mcapi_limits\McapiLimitsInterface::checkPayer()
   */
  public function checkPayer(EntityInterface $wallet, $diff) {
    $limits = $this->getLimits($account);
    return TRUE;
  }

  /**
   * @see \Drupal\mcapi_limits\McapiLimitsInterface::checkPayee()
   */
  public function checkPayee(EntityInterface $wallet, $diff) {
    $limits = $this->getLimits($account);
    return TRUE;
  }

  /**
   * @see \Drupal\mcapi_limits\McapiLimitsBase::getLimits($wallet)
   * @return array
   *   'min' and 'max' native values
   */
  public function getLimits(EntityInterface $wallet){
    $val = $this->limits_settings['liquidity']['value'];
    $limits = array(
      'min' => -$val,
      'max' => $val
    );
    return $limits;
  }

  /**
   *
   * @param unknown $default
   * @return multitype:string number unknown NULL
   */
  public function widget($default) {
    return array(
      '#title' => t('Liquidity per user'),
      '#description' => t('The distance from zero a user can trade'),
      '#type' => 'worth',
      //'#currcodes' => array($this->currency->id()),
      '#default_value' => $default,
      '#min' => 0,
    );
  }

}
