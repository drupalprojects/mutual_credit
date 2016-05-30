<?php

/**
 * @file
 * Contains \Drupal\mcapi\Element\BalanceHistories.
 */

namespace Drupal\mcapi\Element;

/**
 * Provides a form element for selecting a transaction state
 *
 * @RenderElement("balance_histories")
 */
class BalanceHistories extends \Drupal\Core\Render\Element\RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#pre_render' => [
        [get_class($this), 'preRender'],
        [get_class($this), 'processPoints'],
      ],
      '#theme_wrappers' => ['mcapi_wallet_component'],
    ];
  }

  public static function preRender($element) {
    foreach($element['#wallet']->currenciesUsed() as $curr_id => $currency) {
      $points = \Drupal::entityTypeManager()
        ->getStorage('mcapi_transaction')
        ->historyOfWallet($element['#wallet']->id(), $curr_id);

      if (count($points)) {
        //add a start and end points showing the balance at this moment
        $points = [$element['#wallet']->created->value => 0] +
        $points +
        [REQUEST_TIME => end($points)];
      }

      if (count($points) < 2) {
        //don't draw the chart if it is empty
        continue;
      }
      $max = _mcapi_get_axis_max(max(max($points), abs(min($points))));
      $element[$curr_id] = [
        '#theme' => 'mcapi_timeline',
        '#title' => t('@currencyname history', ['@currencyname' => $currency->label()]),//used in the theme_wrapper
        '#width' => $element['#width'],
        '#height' => $element['#width']/2,
        '#currency' => $currency,
        '#points' => $points, //array of balances keyed by timestamp
      ];
      if($currency->issuance == \Drupal\mcapi\Entity\Currency::TYPE_PROMISE) {
        //make symmetrical axes
        //its pretty difficult to give the axes nice limits if the native currency
        //val bears no resemblance to the display value
        $element[$curr_id]['#vaxislabels'] = [
          ['value' => -$max, 'label' => $currency->format(-$max)],
          ['value' => -0, 'label' => 0],
          ['value' => $max, 'label' => $currency->format($max)],
        ];
      }
    }
    return $element;
  }

  static function processPoints($element) {
    foreach (\Drupal\Core\Render\Element::children($element) as $key) {
      $points = &$element[$key]['#points'];
      $point_count = count($points);
      //apply smoothing, or even roughing.
      if ($point_count < $element['#width'] / 3) {
        //step method, for a small number of points
        static::stepped($points);
        static::stepped($points);
      }
      elseif ($point_count > $element['#width']) {
        //decimate the array, for a large number of points
        static::smooth($points, $element['#width']);
      }
    }
    //doing nothing yields angular graph with diagonal lines between points
    return $element;
  }


  /**
   * Converts points from get into a stepped pattern with 2 points for every
   * transation so it shows the correct balance at any given time
   *
   * @param array $points
   * @return array
   *   balances, keyed by by unixtimes
   */
  static function stepped(&$points) {
    $times = $values = [];
    //make two values for each one in the keys and values
    foreach ($points as $time => $bal) {
      $times[] = $time;
      $times[] = $time + 1;
      $values[] = $bal;
      $values[] = $bal;
    }
    //now slide the arrays against each other to create steps
    array_pop($values);
    array_shift($times);
    $points = array_combine($times, $values);
  }

  /**
   * A simple smoothing function which uses the pixel width to get the right resolution
   *
   * @param array $points
   * @return array
   *   balances, keyed by by unixtimes
   */
  static function smooth(&$points, $width) {
    $ratio = $point_count/$width;
    $factor = intval($ratio + 1);
    //now iterate through the array taking 1 out of every $factor values
    $i = 0;
    foreach ($points as $key => $value) {
      if ($i % $factor != 0)
        unset($points[$key]);
      $i++;
    }
  }


}
