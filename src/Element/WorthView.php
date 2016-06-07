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
//    $markup = [
//      '#type' => 'link',
//      '#title' => $currency->format(abs($element['#value']), $element['#format']),
//      '#url' => \Drupal\Core\Url::fromRoute('entity.mcapi_currency.canonical', ['mcapi_currency'=> $element['#currency']]),
//      '#options' => ['html' => TRUE, 'title' => 'blah']
//    ];

    return [
      '#attributes' => [
        'class' => [
          'currency-'.$element['#currency']->id
        ]
      ],
      '#markup' => $element['#currency']->format(abs($element['#value']), $element['#format'])
    ];
  }

}
