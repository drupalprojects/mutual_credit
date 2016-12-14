<?php

namespace Drupal\mcapi_forms\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Radios;

/**
 * Provides a form element for selecting a transaction state.
 *
 * It inherits everything from radios but the trasaction states are autofilled.
 *
 * @FormElement("my_wallets")
 *
 * @deprecated
 */
class MyWallets extends Radios {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    $info = parent::getInfo();
    return [
      '#input' => TRUE,
      '#title_display' => 'before',
      '#process' => [
        [get_class($this), 'processOptions'],
      ],
      '#multiple' => FALSE,
      '#pre_render' => [
        [$class, 'preRenderCompositeFormElement'],
      ],
    ];
  }

  /**
   * Process callback.
   */
  public static function processOptions(&$element, FormStateInterface $form_state, &$complete_form) {


    return Radios::processRadios($element, $form_state, $complete_form);
  }

}
