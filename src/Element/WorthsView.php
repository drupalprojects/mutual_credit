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
   * @todo this should use the theme function and template, but I spent 3 hours and couldn't get the twig not to escape the individual worth values
   */
  public static function preRender(array $element) {
    $element['#values'] = [];

    foreach ($element['#worths'] as  $worth) {
      $subelement = [
        '#type' => 'worth_view',
        '#currency' => \Drupal\mcapi\Entity\Currency::load($worth['curr_id']),
        '#format' => $element['#format'],
        '#value' => $worth['value'],
      ];
      $values[] = render($subelement);
    }
    $element['#markup'] = implode(\Drupal::config('mcapi.settings')->get('delimiter'), $values);
    return $element;
  }

}
