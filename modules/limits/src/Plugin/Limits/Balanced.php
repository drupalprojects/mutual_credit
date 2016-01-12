<?php

/**
 * @file
 *  Contains Drupal\mcapi_limits\Plugin\Limits\Balanced
 *
 */

namespace Drupal\mcapi_limits\Plugin\Limits;

use Drupal\mcapi\Entity\WalletInterface;
use Drupal\Core\Form\FormStateInterface;
Use Drupal\mcapi_limits\Plugin\McapiLimitsBase;


/**
 * No balance limits
 *
 * @Limits(
 *   id = "balanced",
 *   label = @Translation("Balanced"),
 *   description = @Translation("Limits are the same distance from zero")
 * )
 */
class Balanced extends McapiLimitsBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $subform = parent::buildConfigurationForm($form, $form_state);
    //the order seemes to matter more than the weight
    $subform['liquidity'] = [
      '#title' => $this->t('Liquidity per user'),
      '#description' => $this->t('The distance from zero a user can trade'),
      '#type' => 'worth_form',
      '#allowed_curr_ids' => [$this->currency->id()],
      '#default_value' => $this->configuration['liquidity'],
      '#min' => 0
    ];
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
    $val = $this->configuration['liquidity'][0]['value'];
    $limits = array(
      'min' => -$val,
      'max' => $val
    );
    return $limits;
  }

  /**
   * (non-PHPdoc)
   * @see \Drupal\mcapi_limits\Plugin\Limits\McapiLimitsBase::defaultConfiguration()
   */
  public function defaultConfiguration() {
    $defaults = parent::defaultConfiguration();
    return $defaults+ [
      //this is the format the worth widget expects
      'liquidity' => [
        'curr_id' => $this->currency->id(),
          //@todo check this after we have saved a value
        'value' => 1000
      ]
    ];
  }

}
