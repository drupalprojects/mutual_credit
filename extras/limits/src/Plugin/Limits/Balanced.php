<?php

/**
 * @file
 *  Contains Drupal\mcapi_limits\Plugin\Limits\Balanced
 *
 */

namespace Drupal\mcapi_limits\Plugin\Limits;

use Drupal\mcapi\Entity\WalletInterface;
use Drupal\Core\Form\FormStateInterface;


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
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    //the order seemes to matter more than the weight
    $subform['liquidity'] = $this->widget($this->configuration['liquidity']);
    $subform += parent::buildConfigurationForm($form, $form_state);
    return $subform;
  }

  /**
   * @see \Drupal\mcapi_limits\McapiLimitsBase::getLimits($wallet)
   * @return array
   *   'min' and 'max' native values
   */
  public function getLimits(WalletInterface $wallet){
    //the stored value is a 1 item array keyed by curr_id
    //we don't need to lookup the curr_id, we can just get the first value
    $val = reset($this->configuration['liquidity']);
    $limits = array(
      'min' => -$val,
      'max' => $val
    );
    return $limits;
  }

  /**
   * Does this need to be a separate function? It is used on the currency form and on the everride form
   *
   * @param array $default
   * @return array
   *   a form element
   */
  public function widget($default) {
    return array(
      '#title' => t('Liquidity per user'),
      '#description' => t('The distance from zero a user can trade'),
      '#type' => 'worth',
      '#default_value' => $default,
      '#min' => 0,
    );
  }

  /**
   * (non-PHPdoc)
   * @see \Drupal\mcapi_limits\Plugin\Limits\McapiLimitsBase::defaultConfiguration()
   */
  public function defaultConfiguration() {
    $defaults = parent::defaultConfiguration();
    return $defaults+ array(
      //this is the format the worth widget expects
      'liquidity' => array($this->currency->id() => 1000),
    );
  }

}
