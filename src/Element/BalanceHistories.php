<?php

namespace Drupal\mcapi\Element;

use Drupal\mcapi\Entity\Currency;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a visualisation of a wallet's balance history.
 *
 * @RenderElement("balance_histories")
 */
class BalanceHistories extends RenderElement {

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

  /**
   * Prerender callback.
   */
  public static function preRender($element) {
    $storage = \Drupal::entityTypeManager()->getStorage('mcapi_transaction');
    $wid = $element['#wallet']->id();
    // On a large system we don't want to try getting history of all currencies.
    // This is the only occurrence of currenciesUsed
    foreach ($storage->currenciesUsed($wid) as $curr_id) {
      $currency = Currency::load($curr_id);
      $points = $storage->historyOfWallet($wid, $curr_id);

      if (count($points)) {
        // Add a start and end points showing the balance at this moment.
        $points = [$element['#wallet']->created->value => 0] +
        $points +
        [REQUEST_TIME => end($points)];
      }

      if (count($points) < 2) {
        // don't draw the chart if it is empty.
        continue;
      }
      $element[$curr_id] = [
        '#theme' => 'mcapi_timeline',
      // Used in the theme_wrapper.
        '#title' => t('@currencyname history', ['@currencyname' => $currency->label()]),
        '#width' => $element['#width'],
        '#height' => $element['#width'] / 2,
        '#currency' => $currency,
      // Array of balances keyed by timestamp.
        '#points' => $points,
      ];
    }
    return $element;
  }

  /**
   * Process callback.
   */
  public static function processPoints($element) {
    foreach (Element::children($element) as $key) {
      $points = &$element[$key]['#points'];
      $point_count = count($points);
      // Apply smoothing, or even roughing.
      if ($point_count < $element['#width'] / 3) {
        // Step method, for a small number of points.
        static::stepped($points);
        static::stepped($points);
      }
      elseif ($point_count > $element['#width']) {
        // Decimate the array, for a large number of points.
        static::smooth($points, $element['#width']);
      }
    }
    // Doing nothing yields angular graph with diagonal lines between points.
    return $element;
  }

  /**
   * Utility.
   *
   * Converts points from get into a stepped pattern with 2 points for every
   * transation so it shows the correct balance at any given time.
   *
   * @param array $points
   *   Raw balances keyed by transaction unixtime.
   */
  public static function stepped(&$points) {
    $times = $values = [];
    // Make two values for each one in the keys and values.
    foreach ($points as $time => $bal) {
      $times[] = $time;
      $times[] = $time + 1;
      $values[] = $bal;
      $values[] = $bal;
    }
    // Now slide the arrays against each other to create steps.
    array_pop($values);
    array_shift($times);
    $points = array_combine($times, $values);
  }

  /**
   * Utility.
   *
   * A simple smoothing function which uses the pixel width to get the right
   * resolution.
   *
   * @param array $points
   *   Raw balances keyed by transaction unixtime.
   */
  public static function smooth(&$points, $width) {
    $ratio = count($points) / $width;
    $factor = intval($ratio + 1);
    // Now iterate through the array taking 1 out of every $factor values.
    $i = 0;
    foreach ($points as $key => $value) {
      if ($i % $factor != 0) {
        unset($points[$key]);
      }
      $i++;
    }
  }

}
