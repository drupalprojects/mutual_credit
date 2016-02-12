<?php

/**
 * @file
 * Contains \Drupal\mcapi\Element\IntertradingWallet.
 * @deprecated
 */

namespace Drupal\mcapi\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Radios;

/**
 * Provides a form element which returns the appropriate intertrading wallets as a hidden field
 *
 * @FormElement("intertrading_wallet")
 */
class IntertradingWallet extends Radios {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processTypes'],
      ]
    ];
  }

  /**
   * process callback for mcapi_types form element
   *
   * @return array
   *   the processed $element
   */
  static function processTypes($element, $form_state) {
    $element = [
      '#type' => 'value',
      '#value' => \Drupal\mcapi\Exchange::intertradingWalletId()
    ];
    return $element;
  }

  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input == NULL) return;
    return $input;
  }

}