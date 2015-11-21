<?php

/**
 * @file
 * Contains \Drupal\mcapi\Element\States.
 */

namespace Drupal\mcapi\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Radios;
use Drupal\Core\Render\Element\Checkboxes;

/**
 * Provides a form element for selecting a transaction state
 * It inherits everything from radios except the trasaction states are autofilled
 *
 * @FormElement("mcapi_states")
 */
class States extends Radios {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#title_display' => 'before',
      '#process' => [
        [get_class($this), 'processStates'],
      ],
      '#multiple' => FALSE,
      '#pre_render' => [
        [$class, 'preRenderCompositeFormElement'],
      ]
    ];
  }

  public static function processStates(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#options'] = mcapi_entity_label_list('mcapi_state');
    if ($element['#multiple']) {
      $element['#theme_wrappers'] = ['checkboxes'];
     // $element['theme_wrappers'] = array('checkboxes');
      return Checkboxes::processCheckboxes($element, $form_state, $complete_form);
    }
    else {
      $element['#theme_wrappers'] = ['radios'];
      return Radios::processRadios($element, $form_state, $complete_form);
    }
  }
}
