<?php

/**
 * @file
 * Contains \Drupal\mcapi\Element\WorthsView.
 */

namespace Drupal\mcapi\Element;

/**
 * Provides a render element for an multiple worth values
 *
 * @RenderElement("worths_view")
 */
class WorthsView extends \Drupal\Core\Render\Element\RenderElement {

  public function getInfo() {
    return [
      //'#theme' => 'worths',
      '#pre_render' => [
        get_class() . '::preRender',
      ],
    ];
  }

  /**
   * @param array $element
   *   a render element with keys #format, #worths
   *
   * @todo this should use the theme function and template to render worths with many currencies
   * but I spent 3 hours and couldn't get the twig not to escape the individual worth values
   * which is a problem because it means we can't have html in the currency formatting strings
   */
  public static function preRender(array $element) {
    $delimiter = \Drupal::config('mcapi.settings')->get('delimiter');

    foreach ($element['#worths'] as  $worth) {
      //we only render zero value worths if there is only one
      if (count($element['#worths'] > 1) and $worth['value'] == 0) {
        continue;
      }
      $currency = \Drupal\mcapi\Entity\Currency::load($worth['curr_id']);
      $subelement = [
        '#type' => 'worth_view',
        '#currency' => $currency,
        '#format' => $element['#format'],
        '#value' => $worth['value'],
        '#attributes' => [
          'title' => $currency->name,
          'class' => ['worth-'.$currency->id]
        ]
      ];
      $element[] = $subelement;
      $element[] = $delimiter;
    }

    array_pop($element);//remove the last delimiter
    return $element;
  }

}
