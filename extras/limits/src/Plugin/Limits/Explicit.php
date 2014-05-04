<?php

/**
 * @file
 *  Contains Drupal\mcapi_limits\Plugin\Limits\Explicit
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
 *   id = "explicit",
 *   label = @Translation("Explicit"),
 *   description = @Translation("Explicit limits")
 * )
 */
class Explicit extends McapiLimitsBase implements McapiLimitsInterface {
  /**
   * @see \Drupal\mcapi_limits\McapiLimitsBase::settingsForm()
   */
  public function settingsForm() {
    $form['minmax'] =  array(
    	'#type' => 'minmax',
      '#currcode' => $this->currency->id(),
      '#default_value' => $this->limits_settings['minmax']
    );
    $form += parent::settingsForm();
    return $form;
  }

  /**
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
   * @see \Drupal\mcapi_limits\McapiLimitsBase::getLimits()
   */
  public function getLimits(EntityInterface $wallet) {
    $limits = array(
      'min' => $this->limits_settings['minmax']['min']['value'],
      'max' => $this->limits_settings['minmax']['max']['value']
    );
    return $limits;
  }

}
