<?php

/**
 * @file
 * Contains \Drupal\mcapi\Element\WorthView.
 */

namespace Drupal\mcapi\Element;

/**
 * Provides a render element for single worth value
 *
 * @RenderElement("worth_view")
 */
class WorthView extends \Drupal\Core\Render\Element\RenderElement {

  public function getInfo() {
    return [
      '#pre_render' => [
        get_class() . '::preRender',
      ],
      '#minus' => FALSE,
      '#theme' => 'worth',
    ];
  }

  /**
   * @param array $element
   *   has keys #currency and #value
   */
  public static function preRender($element) {
    $currency = $element['#currency'];

    $element['#attributes']['class'] = 'currency-'.$currency->id;
    $markup = '';
    if ($element['#value']) {
      if ($element['#value'] < 0) {
        $markup = '-';
      }
      $markup .= $currency->format(abs($element['#value']), $element['#format']);
    }
    else {
      //apply any special formatting for zero value transactions
      if ($currency->zero) {
        if ($element['#format'] == Currency::DISPLAY_NORMAL) {
          $element['#attributes']['class'][] = 'zero';
          $markup .=  \Drupal::config('mcapi.settings')->get('zero_snippet');
        }
        else {
          $markup .= 0;
        }
      }
      else {
        \Drupal::logger('mcapi')->warning("Zero value shouldn't be possible in currency ".$currency->id);
      }
    }
    if ($element['#minus']) {
      $markup = '-'.$markup;
    }
    $element['#attributes']['title'] = $currency->name;
    //@todo how do we tell twig that this value is already escaped?
    $element['#value'] = $markup;
    return $element;
  }

}
