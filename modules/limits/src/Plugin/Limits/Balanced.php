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
      '#default_value' => $this->configuration['liquidity']['value'],
      '#min' => 0
    ];
    return $subform;
  }


  /**
   * {@inheritdoc}
   */
  public function getLimits(WalletInterface $wallet){
    $limits = [
      'min' => -$this->configuration['liquidity']['value'],
      'max' => $this->configuration['liquidity']['value']
    ];
    return $limits;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $defaults = parent::defaultConfiguration();
    return $defaults + [
      //this is the format the worth widget expects
      'liquidity' => [
        'curr_id' => $this->currency->id(),
        'value' => 1000
      ]
    ];
  }

}
