<?php

namespace Drupal\mcapi\Element;

use Drupal\mcapi\Mcapi;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Radios;
use Drupal\Core\Render\Element\Checkboxes;

/**
 * Provides a form element for selecting a transaction state.
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
      '#title' => $this->t('Transaction type'),
      '#title_display' => 'before',
      '#process' => [
        [$class, 'processTypes'],
      ],
      '#pre_render' => [
        [$class, 'preRenderCompositeFormElement'],
      ],
      '#exclude' => [],
      '#multiple' => FALSE,
    ];
  }

  /**
   * Process callback for mcapi_types form element.
   */
  public static function processTypes($element, FormStateInterface $form_state) {
    $element['#options'] = Mcapi::entityLabelList('mcapi_type');
    foreach ((array) $element['#exclude'] as $type) {
      unset($element['#options'][$type]);
    }
    if ($element['#multiple']) {
      $element['#theme_wrappers'] = ['checkboxes'];
      return Checkboxes::processCheckboxes($element, $form_state, $form_state->getCompleteForm());
    }
    else {
      $element['#theme_wrappers'] = ['select'];
      return Radios::processRadios($element, $form_state, $form_state->getCompleteForm());
    }
    return $element;
  }

  /**
   * Value callback for mcapi_types form element.
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input == NULL) {
      return;
    }
    return $input;
  }

}
