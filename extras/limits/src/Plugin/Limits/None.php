<?php

/**
 * @file
 *  Contains Drupal\mcapi_limits\Plugin\Limits\None
 *
 */

namespace Drupal\mcapi_limits\Plugin\Limits;

use \Drupal\mcapi\Entity\WalletInterface;

/**
 * No balance limits
 *
 * @Limits(
 *   id = "none",
 *   label = @Translation("None"),
 *   description = @Translation("No limits")
 * )
 */
class None extends McapiLimitsBase implements McapiLimitsInterface {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    return array('empty' => array(
    	'#markup' => t('There are no settings for this plugin')
    ));
  }
  /**
   * (non-PHPdoc)
   * @see \Drupal\mcapi_limits\McapiLimitsInterface::checkLimits()
   */
  public function checkLimits(WalletInterface $wallet, $diff){
  	return TRUE;
  }

  public function getBaseLimits(WalletInterface $wallet){
  	return array('min' => NULL, 'max' => NULL);
  }

  public function getLimits(WalletInterface $wallet){
    return array(
    	'min' => NULL,
      'max' => NULL
    );
  }
  public function defaultConfiguration() {
    return array();
  }
}