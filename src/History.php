<?php

/**
 * @file
 *
 * Contains \Drupal\mcapi\History
 * helper class to generate gcharts showing the balance history
 *
 */

namespace Drupal\mcapi;

use Drupal\Core\Cache\Cache;



class History {
  
  /**
   * get some or all all the histories for a given wallet
   * 
   * @param Wallet $wallet
   * @param array $currencies
   *   currencies for which the history is desired, keyed by currency id
   * @param integer $width
   *   the pixel width of the desired image
   * @return array
   *   the histories, keyed by currency id
   */
  public static function getAll($wallet, $currencies, $width) {
    $cache_id = 'wallet:timesbalances:'.implode('.', array_keys($currencies)).':'.$wallet->id();
    //@todo reinstate the cache here
    if (0 && $cache = \Drupal::cache()->get($cache_id)) {
      $histories = $cache->data;
    }
    else {
      foreach ($currencies as $id => $currency) {
        $points = Self::get($wallet, $currency->id());
        $point_count = count($points);
        if ($point_count) {
          //add a final point showing the balance at this moment
          $points[REQUEST_TIME] = end($points);
        }
        //apply smoothing, or even roughing.
        //step method, for a small number of points
        if ($point_count < $width / 3) {
          $histories[$id] = Self::stepped($points);
        }
        //decimate the array, for a large number of points
        elseif ($point_count > $width) {
          $histories[$id] = Self::smooth($points, $width);
        }
        //angular graph with diagonal lines between transactions
        else {
          $histories[$id] = $points; 

        }
      }
      \Drupal::cache()->set(
        $cache_id,
        $histories,
        Cache::PERMANENT,
        ['mcapi_wallet:'.$wallet_id]
      );
    }
    return $histories;
  }
  
  /**
   * Get the history of one currency in one wallet from the transaction storage 
   * controller.
   * 
   * @param integer $wallet_id
   * @param string $currency_id
   * @return array
   *   balances, keyed by by unixtimes
   */
  function get($wallet_id, $currency_id) {
    return \Drupal::entityManager()
      ->getStorage('mcapi_transaction')
      ->timesBalances($wallet_id, $currency_id);
  }
  
  /**
   * Converts points from get into a stepped pattern with 2 points for every 
   * transation so it shows the correct balance at any given time
   * 
   * @param array $points
   * @return array
   *   balances, keyed by by unixtimes
   */
  static function stepped($points) {
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
    return array_combine($times, $values);
  }
  
  /**
   * A simple smoothing function which uses the pixel width to get the right resolution
   * 
   * @param array $points
   * @return array
   *   balances, keyed by by unixtimes
   */
  static function smooth($points, $width) {
    $ratio = $point_count/$width;
    $factor = intval($ratio + 1);
    //now iterate through the array taking 1 out of every $factor values
    $i = 0;
    foreach ($points as $key => $value) {
      if ($i % $factor != 0)
        unset($history[$key]);
      $i++;
    }
  }
  
  /**
   * Calculate some good axis labels, using the min & max balance extents
   * 
   * @param array $vals
   *   the $points history of balances keyed by unixtimes
   * @return array
   *   values keyed by min, 0 & max
   */
  static function axes($vals) {
    $max = max($vals);
    $min = min($vals);
    if ($min >= 0) {
      $max = _mcapi_get_axis_max($max);
      return [0, $max / 2, $max];
    }
    elseif ($max <= 0) {
      $min = -_mcapi_get_axis_max(abs($min));
      return [$min, $min / 2, 0];
    }
    else {
      return [-_mcapi_get_axis_max(abs($min)), 0, _mcapi_get_axis_max($max)];
    }
  }
  
  
}
