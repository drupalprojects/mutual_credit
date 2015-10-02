<?php

/*
 * @file
 * Definition of Drupal\mcapi_limits\WalletLimiter.
 */

namespace Drupal\mcapi_limits;

use Drupal\mcapi\CurrencyInterface;
use Drupal\mcapi\WalletInterface;
use Drupal\mcapi\Exchange;

class WalletLimiter {//couldn't be bothered to make an interface for this

  /**
   * The wallet we are working with
   */
  private $wallet;

  /**
   * an array keyed by currency ids each containing a min and a max value
   * Needed in every public function
   */
  private $limits;
  
  /**
   * The database 
   */
  private $database;
  
  public function __construct($database) {
    $this->database = $database;
  }

  public function setWallet(WalletInterface $wallet){
    $this->wallet = $wallet;
    $this->limits = $this->getLimits(TRUE);
    return $this;
  }

  /**
   * prepare the $this->limits
   *
   * @return \Drupal\mcapi_limits\WalletLimiter
   */
  public function getLimits($refresh = TRUE) {
    if ($this->limits && !$refresh) {
      return $this->limits;
    }
    $limits = [];
    //get the default limits
    $needed_currencies = Exchange::currenciesAvailable($this->wallet);
    foreach ($needed_currencies as $curr_id => $currency) {
      $limits[$curr_id] = $this->defaults($currency);
    }
    //get the per-wallet overridden values
    foreach ($this->load() as $curr_id => $minmax) {
      if (isset($minmax['min'])) {
        $limits[$curr_id]['min'] = $minmax['min'];
      }
      if (isset($minmax['max'])) {
        $limits[$curr_id]['max'] = $minmax['max'];
      }
    }
    //then add in the currencies with no limits
    foreach (array_diff_key($needed_currencies, $this->limits) as $curr_id => $currency) {
      $limits[$curr_id] = ['min' => NULL, 'max' => NULL];
    }
    return $limits;
  }

  /**
   * get the raw max value of this wallet in this currency
   *
   * @param type $curr_id
   *
   * @return integer
   */
  public function max($curr_id){
    return $this->limits[$curr_id]['max'];
  }

  /**
   * get the raw min value of this wallet in this currency
   *
   * @param type $curr_id
   *
   * @return integer
   */
  public function min($curr_id){
    return $this->limits[$curr_id]['min'];
  }

  /**
   * get the difference between the given amount (usually the balance), and the max limit
   * @param string $curr_id
   * @param integer $balance
   *   the raw quantity value
   *
   * @note the result is > 0 while the balance is greater than the min
   */
  public function spend_limit($curr_id, $balance) {
    $min = $limiter->min($currency);
    if (isset($min)) {
      return -$min - $balance;
    }
  }

  /**
   * get the difference between the given amount (usually the balance), and the min limit
   * @param string $curr_id
   * @param integer $balance
   *   the raw quantity value
   * *
   * @note the result is > 0 while the balance is less than the max
   */
  public function earn_limit($curr_id, $balance) {
    $max = $limiter->max($currency);
    if (isset($max)) {
      return $max- $balance;
    }
  }

  function __toString() {
    drupal_set_message('use the theme system to visualise wallets');

    foreach ($this->limits as $curr_id => $limits) {
      $currency = mcapi_currency_load($curr_id);
      $row = [];
      if (!is_null($limits['min'])) {
        $row[] = t('Min !quant', ['!quant' => $currency->format($limits['min'])]);
      }
      if (!is_null($limits['max'])) {
        $row[] = t('Max !quant', ['!quant' => $currency->format($limits['max'])]);
      }
      if ($row) $output[] = $row;
    }
    return implode(' | ', $output);
  }

  /**
   * get the limits for the given currency according to settings on the currency page.
   * Overrides not considered
   *
   * @param CurrencyInterface $currency
   *
   * @return array
   *   the min and max limits
   *
   * @deprecated
   */
  public function defaults($currency) {
    return \Drupal::service('plugin.manager.mcapi_limits')
      ->createInstanceCurrency($currency)
      ->getLimits($this->wallet);
  }

  /*
   * return the values for all currencies in the wallet which
   * the user can use, taking overrides into account
   *
   * @return array
   *
   * saved limit overrides, keyed by curr_id. Each override is an array with
   * min, max, editor, & date(unixtime)
   */
  private function load() {
    $result = [];
    foreach (Exchange::currenciesAvailable($this->wallet) as $currency) {
      $config = $currency->getThirdPartySettings('mcapi_limits');
      if (!empty($config['override'])) {
        $overridable_curr_ids[] = $currency->id();
      }
    }
    if (empty($overridable_curr_ids))return [];
    //if there is no override value saved, nothing will be returned
    $limits = $this->database->select('mcapi_wallets_limits', 'l')
      ->fields('l', array('curr_id', 'max', 'value', 'editor', 'date'))
      ->condition('wid', $this->wallet->id())
      ->condition('curr_id', $overridable_curr_ids)
      ->execute();
    while ($limit = $limits->fetch()) {
      $key = $limit->max ? 'max' : 'min';
      $result[$limit->curr_id] = [
        $key => $limit->value,
        'editor' => $limit->editor,
        'date' => $limit->date
      ];
    }
    return $result;
  }
}
