<?php

/*
 * @file
 * Definition of Drupal\mcapi_limits\WalletLimits.
 */

namespace Drupal\mcapi_limits;

use Drupal\mcapi\Entity\CurrencyInterface;
use Drupal\mcapi\Entity\WalletInterface;


/**
 * I wanted to make a decorator class, but it turned into something almost as good, I think.
 * this class takes the wallet as a constructor argument, but doesn't implement the methods of the wallet class
 */
class WalletLimits {//couldn't be bothered to make an interface for this

  private $wallet;
  private $limits;

  public function __construct(WalletInterface $wallet){
    $this->wallet = $wallet;
  }

  //not all of these will be needed
  public function max(CurrencyInterface $currency, $overriden = TRUE){
    $this->calc();
    return $this->limits[$currency->id()]['max'];
  }
  public function min(CurrencyInterface $currency, $overriden = TRUE){
    $this->calc();
    return $this->limits[$currency->id()]['min'];
  }
  public function maxes($overriden = TRUE){
    $this->calc();
    foreach ($this->limits as $curr_id => $minmax) {
      $maxes[$curr_id] = $minmax['max'];
    }
    return $maxes;
  }
  public function mins($overriden = TRUE){
    $this->calc();
    foreach ($this->limits as $curr_id => $minmax) {
      $min[$curr_id] = $minmax['min'];
    }
    return $mins;
  }

  /**
   * calculate limits of all currencies
   *
   * @param array $currencies
   *   one or many objects implement ConfigEntityInterface
   *
   * @param boolean $overridden
   *   if TRUE returns the personal override values where flag is enabled and overrides exist
   *
   * @return array
   *   keys are available currencies values are arrays of max and min
   *   (Overridden values also show an editor user id and unixtime)
   */
  public function calc($overridden = TRUE){
    if (isset($this->limits)) return;
    $needed_currencies = $this->wallet->currencies_all();
    //first get the overridden values
    $override_curr_ids = array();
    if ($overridden) {
      $this->limits = array();
      foreach ($needed_currencies as $currency) {
        $config = mcapi_limits_saved_plugin($currency)->getConfiguration();
        if (!empty($config['override'])) {
          $override_curr_ids[] = $currency->id();
        }
      }
      $this->limits = $this->saved_overrides($override_curr_ids) + $this->limits;
    }
    //then get the default limits
    foreach (array_diff_key($needed_currencies, $this->limits) as $curr_id => $currency) {
      //now get the defaults values for any need currencies not already loaded
      $this->limits[$curr_id] = $this->default_limits($currency);
    }
    //then fill in the ones with no limits
    foreach (array_diff_key($needed_currencies, $this->limits) as $curr_id => $currency) {
      $this->limits[$curr_id] = array('min' => NULL, 'max' => NULL);
    }
    return $this->limits;
  }

  /**
   * get the min and max limits for one currency
   */
  function limits($curr_id) {
    $this->calc();
    return $this->limits[$curr_id];
  }

  /**
   * get the limits for the given currency according to settings on the currency page.
   * Overrides not considered
   * @param CurrencyInterface $currency
   * @return array
   *   the min and max limits
   */
  public function default_limits($currency) {
    return mcapi_limits_saved_plugin($currency)->getLimits($this->wallet);
  }


  private function saved_overrides($override_curr_ids) {
    if (empty($override_curr_ids)) return array();
    //if there is no override value saved, nothing will be returned
    return db_select('mcapi_wallets_limits', 'l')
      ->fields('l', array('curr_id', 'min', 'max', 'editor', 'date'))
      ->condition('wid', $this->wallet->id())
      ->condition('curr_id', $override_curr_ids)
      ->execute()->fetchAllAssoc('curr_id', \PDO::FETCH_ASSOC);
  }
}
