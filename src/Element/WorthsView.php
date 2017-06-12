<?php

namespace Drupal\mcapi\Element;

use Drupal\mcapi\Entity\Currency;
use Drupal\mcapi\Entity\CurrencyInterface;
use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a render element for an multiple worth values.
 *
 * The context property determines how and whether zero worths will be rendered
 *
 * @RenderElement("worths_view")
 */
class WorthsView extends RenderElement {

  const MODE_TRANSACTION = 'transaction';
  const MODE_BALANCE = 'balance';
  const MODE_OTHER = 'other';

  public static function options() {
    return [
      SELF::MODE_TRANSACTION => t('Transaction field'),
      SELF::MODE_BALANCE =>  t('Wallet balance'),
      SELF::MODE_OTHER => t('Other')
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#pre_render' => [
        get_class() . '::preRender',
      ],
      '#format' => CurrencyInterface::DISPLAY_NORMAL,
      // Can have 3 values, transaction, balance and other.
      '#context' => SELF::MODE_TRANSACTION
    ];
  }

  /**
   * Prerender callback.
   */
  public static function preRender(array $element) {
    $delimiter = \Drupal::config('mcapi.settings')->get('worths_delimiter');
    $w = 0;

    foreach ($element['#worths'] as $worth) {
      // We only render zero value worths if there is only one.
      if (count($element['#worths']) > 1 and $worth['value'] == 0 and !$element['#context'] <> 'other') {
        continue;
      }
      $currency = Currency::load($worth['curr_id']);
      if ($element['#context'] == 'transaction' and $currency->zero and $worth['value'] == 0) {
        $subelement['#markup'] = \Drupal::config('mcapi.settings')->get('zero_snippet');
      }
      else {
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
      }
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
