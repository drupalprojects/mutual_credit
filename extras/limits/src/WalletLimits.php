<?php

/*
 * @file
 * Definition of Drupal\mcapi_limits\WalletLimits.
 */

namespace Drupal\mcapi_limits;

use Drupal\mcapi\CurrencyInterface;
use Drupal\mcapi\WalletInterface;
use Drupal\mcapi\Entity\Currency;
use Drupal\mcapi\Exchange;


/**
 * I wanted to make a decorator class, but it turned into something almost as good, I think.
 * this class takes the wallet as a constructor argument, but doesn't implement the methods of the wallet class
 */
class WalletLimits {//couldn't be bothered to make an interface for this

  private $wallet;
  private $limits;

  public function __construct(WalletInterface $wallet){
    $this->wallet = $wallet;
    
    $needed_currencies = Exchange::currenciesAvailable($this->wallet);
    $this->limits = [];
    //get the default limits
    foreach ($needed_currencies as $curr_id => $currency) {
      $this->limits[$curr_id] = $this->default_limits($currency);
    }

    //get the per-wallet overridden values
    foreach ($this->saved_overrides() as $curr_id => $minmax) {
      if (isset($minmax['min'])) {
        $this->limits[$curr_id]['min'] = $minmax['min'];
      }
      if (isset($minmax['max'])) {
        $this->limits[$curr_id]['max'] = $minmax['max'];
      }
    }
    //then add in the currencies with no limits
    foreach (array_diff_key($needed_currencies, $this->limits) as $curr_id => $currency) {
      $this->limits[$curr_id] = array('min' => NULL, 'max' => NULL);
    }
  }

  //not all of these will be needed
  public function max($curr_id, $formatted = FALSE){
    $val = $this->limits[$curr_id]['max'];
    if ($formatted && is_numeric($val)) {
      return Currency::load($curr_id)->format($val);
    }
    return $val;
  }
  public function min($curr_id, $formatted = FALSE){
    $val = $this->limits[$curr_id]['min'];
    if ($formatted && is_numeric($val)) {
      return Currency::load($curr_id)->format($val);
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
   * get the min and max limits for one currency
   */
  function limits($curr_id) {
    if (array_key_exists($curr_id, $this->limits)) {
      return $this->limits[$curr_id];
    }
    //else the wallet has stopped trading so limits are irrelevant
    return array('min' => NULL, 'max' => NULL);
  }

  function __toString() {
    foreach ($this->limits as $curr_id => $limits) {
      $currency = mcapi_currency_load($curr_id);
      $row = [];
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
   * min, max, editor, & date(unixtime)
   */
  public function saved_overrides() {
    $result = [];
    foreach (Exchange::currenciesAvailable($this->wallet) as $currency) {
      $config = $currency->getThirdPartySettings('mcapi_limits');
      if (!empty($config['override'])) {
        $overridable_curr_ids[] = $currency->id();
      }
    }
    if (empty($overridable_curr_ids))return [];
    //if there is no override value saved, nothing will be returned
    $limits = db_select('mcapi_wallets_limits', 'l')
      ->fields('l', array('curr_id', 'max', 'value', 'editor', 'date'))
      ->condition('wid', $this->wallet->id())
      ->condition('curr_id', $overridable_curr_ids)
      ->execute();
    while ($limit = $limits->fetch()) {
      $key = $limit->max ? 'max' : 'min';
      $result[$limit->curr_id][$key] = $limit->value;
      $result[$limit->curr_id]['editor'] = $limit->editor;
      $result[$limit->curr_id]['date'] = $limit->date;
    }
    return $result;
  }
}
