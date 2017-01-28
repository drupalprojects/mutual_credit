<?php

namespace Drupal\mcapi\Element;

use Drupal\mcapi\Entity\Currency;
use Drupal\mcapi\Mcapi;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Render\Element\Checkboxes;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a widget to select currencies.
 *
 * @FormElement("mcapi_currency_select")
 */
class CurrencySelect extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return array(
      '#input' => TRUE,
      '#process' => [
        [get_class($this), 'processCurrcodes'],
      ],
      '#theme_wrappers' => ['form_element'],
      '#multiple' => FALSE,
    );
  }

  /**
   * Process callback.
   */
  public static function processCurrcodes($element, $form_state) {
    $conditions = [];
    if (empty($element['#options']) && !empty($element['#curr_ids'])) {
      // Shows the intersection of all currencies and currencies provided.
      $element['#options'] = array_intersect_key(
        Mcapi::entityLabelList('mcapi_currency', $element['#curr_ids']),
        Currency::loadByProperties($conditions)
      );
    }
    elseif (empty($element['#options'])) {
      $element['#options'] = Mcapi::entityLabelList('mcapi_currency', $conditions);
    }
    elseif ($element['#options'] == 'all') {
      $element['#options'] = Mcapi::entityLabelList('mcapi_currency');
    }
    // For some reason the hidden field doesn't appear in $request->request->all()
//    if (count($element['#options']) == 1) {
//      $element['#type'] = 'hidden';
//      $element['#value'] = key($element['#options']);
//      $element['#default_value'] = key($element['#options']);
//      unset($element['#theme_wrappers'], $element['#options']);
//    }
//    else
    if ($element['#multiple']) {
      // Have to do some of the checkbox processing manually coz we missed it.
      $element['#type'] = 'checkboxes';
      $element['#value'] = array_filter($element['#default_value']);
      $element = Checkboxes::processCheckboxes($element, $form_state, $form_state->getCompleteForm());
    }
    else {
      $element['#theme'] = 'select';
    }
    return $element;
  }

}
