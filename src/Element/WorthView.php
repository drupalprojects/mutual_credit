<?php

namespace Drupal\mcapi\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a render element for single worth value.
 *
 * @RenderElement("worth_view")
 */
class WorthView extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#pre_render' => [
        get_class() . '::preRender',
      ],
      '#minus' => FALSE,
    ];
  }

  /**
   * Prerender callback.
   *
   * @param array $element
   *   Has keys #currency and #value.
   */
  public static function preRender($element) {
    return [
      '#attributes' => [
        'class' => [
          'currency-' . $element['#currency']->id,
        ],
      ],
      '#markup' => $element['#currency']->format(abs($element['#value']), $element['#format']),
    ];
  }

}
