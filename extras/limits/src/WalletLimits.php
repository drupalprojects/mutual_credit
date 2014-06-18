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
    $this->calc();
  }

  //not all of these will be needed
  public function max($curr_id, $formatted = FALSE){
    $val = $this->limits[$curr_id]['max'];
    if ($formatted && is_numeric($val)) {
      $currency = entity_load('mcapi_currency', $curr_id);
      return $currency->format($val);
    }
    return $val;
  }
  public function min($curr_id, $formatted = FALSE){
    $val = $this->limits[$curr_id]['min'];
    if ($formatted && is_numeric($val)) {
      $currency = entity_load('mcapi_currency', $curr_id);
      return $currency->format($val);
    }
    return $val;
  }
  public function maxes($formatted = FALSE){
    foreach (array_keys($this->limits) as $curr_id) {
      $maxes[$curr_id] = $this->max($curr_id, $formatted);
    }
    return $maxes;
  }
  public function mins($formatted = FALSE){
    foreach (array_keys($this->limits) as $curr_id) {
      $mins[$curr_id] = $this->min($curr_id, $formatted);
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
  public function calc(){
    $needed_currencies = $this->wallet->currencies_available();

    //get the default limits
    foreach ($needed_currencies as $curr_id => $currency) {
      $this->limits[$curr_id] = $this->default_limits($currency);
    }

    //first get the overridden values
    foreach ($this->saved_overrides() as $curr_id => $minmax) {
      if (!is_null($minmax['min'])) {
        $this->limits[$curr_id]['min'] = $minmax['min'];
      }
      if (!is_null($minmax['max'])) {
        $this->limits[$curr_id]['max'] = $minmax['max'];
      }
    }
    //then add in the currencies with no limits
    foreach (array_diff_key($needed_currencies, $this->limits) as $curr_id => $currency) {
      $this->limits[$curr_id] = array('min' => NULL, 'max' => NULL);
    }
  }

  /**
   * get the min and max limits for one currency
   */
  function limits($curr_id) {
    if (array_key_exists($curr_id, $this->limits)) {
      return $this->limits[$curr_id];
    }
    else {
      //this should never happen
      drupal_set_message('Currency '.$curr_id.' is not available to wallet '. $this->wallet->id());
      //TODO log this event
      return array('min' => NULL, 'max' => NULL);
    }
  }

  function __toString() {
    foreach ($this->limits as $curr_id => $limits) {
      $currency = mcapi_currency_load($curr_id);
      $row = array();
      if (!is_null($limits['min'])) {
        $row[] = t('Min !quant', array('!quant' => $currency->format($limits['min'])));
      }
      if (!is_null($limits['max'])) {
        $row[] = t('Max !quant', array('!quant' => $currency->format($limits['max'])));
      }
      if ($row) $output[] = $row;
    }
    return implode(' | ', $output);
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

  /*
   * return the override values for all currencies in the wallet which
   * the user can use, are overridable
   *
   * @return array
   * saved limit overrides, keyed by curr_id. Each override is an array with
   * curr_id, min, max, editor, & date(unixtime)
   */
  public function saved_overrides() {
    foreach ($this->wallet->currencies_available() as $currency) {
      $config = mcapi_limits_saved_plugin($currency)->getConfiguration();
      if (!empty($config['override'])) {
        $overridable_curr_ids[] = $currency->id();
      }
    }
    if (empty($overridable_curr_ids))return array();
    //if there is no override value saved, nothing will be returned
    return db_select('mcapi_wallets_limits', 'l')
      ->fields('l', array('curr_id', 'min', 'max', 'editor', 'date'))
      ->condition('wid', $this->wallet->id())
      ->condition('curr_id', $overridable_curr_ids)
      ->execute()->fetchAllAssoc('curr_id', \PDO::FETCH_ASSOC);
  }
}
