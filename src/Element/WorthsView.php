<?php

namespace Drupal\mcapi\Element;

use Drupal\mcapi\Entity\Currency;
use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a render element for an multiple worth values.
 *
 * @RenderElement("worths_view")
 */
class WorthsView extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#pre_render' => [
        get_class() . '::preRender',
      ],
      '#format' => Currency::DISPLAY_NORMAL,
    ];
  }

  /**
   * Prerender callback.
   *
   * @todo this should use the theme function and template to render worths with
   *  many currencies but I spent 3 hours and couldn't get the twig not to
   *  escape the individual worth values which is a problem because it means we
   *  can't have html in the currency formatting strings
   */
  public static function preRender(array $element) {
    $delimiter = \Drupal::config('mcapi.settings')->get('worths_delimiter');
    $w = 0;
    foreach ($element['#worths'] as $worth) {
      // We only render zero value worths if there is only one.
      if (count($element['#worths'] > 1) and $worth['value'] == 0) {
        continue;
      }
      $currency = Currency::load($worth['curr_id']);
      $subelement = [
        '#type' => 'worth_view',
        '#currency' => $currency,
        '#format' => $element['#format'],
        '#value' => $worth['value'],
        '#weight' => $w++,
        '#attributes' => [
          'title' => $currency->name,
          'class' => ['worth-' . $currency->id],
        ],

      ];
      $element[] = $subelement;
      $element[] = [
        '#markup' => $delimiter,
        '#weight' => $w++,
      ];
    }
    // Remove the last delimiter.
    array_pop($element);

    return $element;
  }

}
