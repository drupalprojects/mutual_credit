<?php

namespace Drupal\mcapi\Element;

use Drupal\mcapi\Entity\Currency;
use Drupal\mcapi\Mcapi;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Render\Element\Checkboxes;

/**
 * Provides a widget to select currencies.
 *
 * @FormElement("mcapi_currency_select")
 */
class Currencies extends FormElement {

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
    // Filter only for active currences.
      '#status' => TRUE,
    );
  }

  /**
   * Process callback.
   */
  public static function processCurrcodes($element, $form_state) {
    $conditions = [];
    if ($element['#status']) {
      $conditions['status'] = TRUE;
    }
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
    if (count($element['#options']) == 1) {
      $element['#type'] = 'hidden';
      $element['#value'] = key($element['#options']);
      $element['#default_value'] = key($element['#options']);
      unset($element['#theme_wrappers'], $element['#options']);
    }
    elseif ($element['#multiple']) {
      // Have to do some of the checkbox processing manually coz we missed it.
      $element['#type'] = 'checkboxes';
      $element['#value'] = array_filter($element['#default_value']);
      $element = Checkboxes::processCheckboxes($element, $form_state);
    }
    else {
      $element['#theme'] = 'select';
    }
    return $element;
  }

}
