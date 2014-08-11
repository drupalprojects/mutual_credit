<?php

/**
 * @file
 *  Contains Drupal\mcapi_limits\Plugin\Limits\Explicit
 *
 */

namespace Drupal\mcapi_limits\Plugin\Limits;

use \Drupal\mcapi\Entity\WalletInterface;
use Drupal\Core\Form\FormStateInterface;

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
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $args = func_get_args();
    $subform['minmax'] =  array(
    	'#type' => 'minmax',
      '#default_value' => array(
        'min' => $this->configuration['minmax']['min'][0]['value'],
        'max' => $this->configuration['minmax']['max'][0]['value']
      ),
      '#curr_id' => array_pop($args)->id()
    );
    $subform += parent::buildConfigurationForm($form, $form_state);
    return $subform;
  }

  /**
   * @see \Drupal\mcapi_limits\McapiLimitsBase::getLimits()
   */
  public function getLimits(WalletInterface $wallet) {
    $curr_id = $this->currency->id();
    //TODO this assumes that both values are set
    //the format is a bit inelegant because it is saved directly from the minmax widget
    $min_item = reset($this->configuration['minmax']['min']);
    $max_item = reset($this->configuration['minmax']['max']);
    return array(
      'min' => $min_item['value'],
      'max' => $max_item['value']
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
