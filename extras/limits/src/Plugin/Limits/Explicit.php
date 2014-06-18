<?php

/**
 * @file
 *  Contains Drupal\mcapi_limits\Plugin\Limits\Explicit
 *
 */

namespace Drupal\mcapi_limits\Plugin\Limits;

use \Drupal\mcapi\Entity\WalletInterface;

/**
 * No balance limits
 *
 * @Limits(
 *   id = "explicit",
 *   label = @Translation("Explicit"),
 *   description = @Translation("Explicit limits")
 * )
 *
 */
class Explicit extends McapiLimitsBase implements McapiLimitsInterface {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    $subform['minmax'] =  array(
    	'#type' => 'minmax',
      '#default_value' => $this->configuration['minmax']
    );
    $subform += parent::buildConfigurationForm($form, $form_state);
    return $subform;
  }

  /**
   * @see \Drupal\mcapi_limits\McapiLimitsBase::getLimits()
   */
  public function getLimits(WalletInterface $wallet) {
    return array(
      'min' => reset($this->configuration['minmax']['min']),
      'max' => reset($this->configuration['minmax']['max'])
    );
  }


  /**
   * (non-PHPdoc)
   * @see \Drupal\mcapi_limits\Plugin\Limits\McapiLimitsBase::defaultConfiguration()
   */
  public function defaultConfiguration() {
    $defaults = parent::defaultConfiguration();
    return $defaults+ array(
      'minmax' => array('min' => -1000, 'max' => 1000)
    );
  }

}
