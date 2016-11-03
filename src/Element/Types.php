<?php

namespace Drupal\mcapi\Element;

use Drupal\mcapi\Mcapi;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Radios;
use Drupal\Core\Render\Element\Select;
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
      '#exclude' => [],
      '#multiple' => FALSE,
    ];
  }

  /**
   * Process callback for mcapi_types form element.
   */
  public static function processTypes($element, FormStateInterface $form_state) {
    $element['#options'] = Mcapi::entityLabelList('mcapi_type');
    $form = $form_state->getCompleteForm();
    foreach ((array) $element['#exclude'] as $type) {
      unset($element['#options'][$type]);
    }
    if ($element['#multiple']) {
      $element['#theme_wrappers'] = ['checkboxes'];
      return Checkboxes::processCheckboxes($element, $form_state, $form);
    }
    elseif ($element['#required']) {
      //$element['#theme_wrappers'] = ['select'];
      $element = parent::preRenderCompositeFormElement($element);
      return parent::processRadios($element, $form_state, $form);
    }
    else {
      $element['#type'] = 'select';
      $element['#theme'][] = 'select';
      $element['#theme_wrappers'][] = 'form_element';
      $element['#empty_value'] = '';
      return Select::processSelect($element, $form_state, $form);
    }
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
