<?php

namespace Drupal\mcapi\Element;

use Drupal\mcapi\Exchange;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Radios;

/**
 * A hidden form element with the appropriate intertrading wallets.
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
      ],
    ];
  }

  /**
   * Process callback for mcapi_types form element.
   *
   * @return array
   *   The processed $element.
   */
  public static function processTypes($element, $form_state) {
    $element = [
      '#type' => 'value',
      '#value' => Exchange::intertradingWalletId(),
    ];
    return $element;
  }

  /**
   * Value callback.
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input == NULL) {
      return;
    }
    return $input;
  }

}
