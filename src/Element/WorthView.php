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
    ];
  }

  /**
   * @param array $element
   *   has keys #currency and #value
   */
  public static function preRender($element) {
    $currency = $element['#currency'];
    $element['#attributes']['class'][] = 'currency-'.$currency->id;
    $markup = '';
    
    if ($element['#value'] < 0) {
      $markup = '-';
    }
    $markup .= $currency->format(abs($element['#value']), $element['#format']);
    
    if ($element['#minus']) {
      $markup .= '-'.$markup;
    }
    return [
      '#markup' => \Drupal\Core\Render\Markup::create($markup),
    ];
  }

}
