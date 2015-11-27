<?php

/**
 * @file
 *  Contains Drupal\mcapi_limits\Plugin\Limits\Explicit
 *
 */

namespace Drupal\mcapi_limits\Plugin\Limits;

use Drupal\mcapi\Entity\WalletInterface;
use Drupal\Core\Form\FormStateInterface;
Use Drupal\mcapi_limits\Plugin\McapiLimitsInterface;
Use Drupal\mcapi_limits\Plugin\McapiLimitsBase;

/**
 * No balance limits
 *
 * @Limits(
 *   id = "explicit",
 *   label = @Translation("Explicit"),
 *   description = @Translation("Explicit limits")
 * )
 */
class Explicit extends McapiLimitsBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $args = func_get_args();
    $conf = $this->configuration['minmax'];
    $subform['minmax'] =  [
      '#type' => 'minmax',
      '#default_value' => [
        'min' => $conf['min'] ? $conf['min'][0]['value'] : '',
        'max' => $conf['max'] ? $conf['max'][0]['value'] : ''
      ],
      '#curr_id' => array_pop($args)->id()
    ];

    $subform += parent::buildConfigurationForm($form, $form_state);
    return $subform;
  }

  /**
   * {@inheritdoc}
   */
  public function getLimits(WalletInterface $wallet) {
    $curr_id = $this->currency->id();
    //the format is a bit inelegant because it is saved directly from the minmax widget
    $min_item = reset($this->configuration['minmax']['min']);
    $max_item = reset($this->configuration['minmax']['max']);
    return [
      'min' => $min_item['value'],
      'max' => $max_item['value']
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $defaults = parent::defaultConfiguration();
    return $defaults + [
      'minmax' => [
        'min' => [['value' => -1000]],
        'max' => [['value' => 1000]]
      ]
    ];
  }

}
