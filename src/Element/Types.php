<?php

/**
 * @file
 * Contains \Drupal\mcapi\Element\Types.
 */

namespace Drupal\mcapi\Element;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Radios;
use Drupal\Core\Render\Element\Checkboxes;

/**
 * Provides a form element for selecting a transaction state
 *
 * @FormElement("mcapi_types")
 */
class Types extends Radios {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#title_display' => 'before',
      '#process' => [
        [$class, 'processTypes'],
      ],
      '#pre_render' => [
        [$class, 'preRenderCompositeFormElement'],
      ],
      '#multiple' => FALSE,
    ];
  }

  /**
   * process callback for mcapi_types form element
   *
   * @return array
   *   the processed $element
   */
  static function processTypes($element, $form_state) {
    $element['#options'] = mcapi_entity_label_list('mcapi_type');
    if ($element['#multiple']) {
      $element['#theme_wrappers'] = ['checkboxes'];
     // $element['theme_wrappers'] = array('checkboxes');
      return Checkboxes::processCheckboxes($element, $form_state, $complete_form);
    }
    else {
      $element['#theme_wrappers'] = ['select'];
      return Radios::processRadios($element, $form_state, $complete_form);
    }
    return $element;
  }

  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input == NULL) return;
    return $input;
  }

}