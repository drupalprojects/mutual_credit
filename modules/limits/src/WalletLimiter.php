<?php

/*
 * @file
 * Definition of Drupal\mcapi_limits\WalletLimiter.
 */

namespace Drupal\mcapi_limits;

use Drupal\mcapi\Entity\CurrencyInterface;
use Drupal\mcapi\Entity\WalletInterface;
use Drupal\mcapi\Currency;

class WalletLimiter {//couldn't be bothered to make an interface for this

  /**
   * The wallet we are working with
   */
  private $wallet;

  /**
   * an array keyed by currency ids each containing a min and a max value
   * Needed in every public function
   */
  private $limits = [];

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
   */
  public function getLimits($refresh = TRUE) {
    if ($this->limits && !$refresh) {
      return $this->limits;
    }
    $limits = [];
    //get the default limits
    $needed_currencies = $this->wallet->currenciesAvailable();
    foreach ($needed_currencies as $curr_id => $currency) {
      $limits[$curr_id] = $this->defaults($currency);
    }
    //overwrite defaults with any per-wallet overridden values
    foreach ($this->overrides() as $curr_id => $rows) {
      foreach ($rows as $limit => $vals) {
        $limits[$curr_id][$limit] = $vals['value'];
      }
    }

    //then add in the currencies with no limits
    foreach (array_diff_key($needed_currencies, $limits) as $curr_id => $currency) {
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
    foreach ($this->limits as $curr_id => $limits) {
      $currency = Currency::load($curr_id);
      $row = [];
      if (!is_null($limits['min'])) {
        $row[] = t('Min %quant', ['%quant' => $currency->format($limits['min'])]);
      }
      if (!is_null($limits['max'])) {
        $row[] = t('Max %quant', ['%quant' => $currency->format($limits['max'])]);
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
   */
  public function defaults($currency) {
    return \Drupal::service('plugin.manager.mcapi_limits')
      ->createInstanceCurrency($currency)
      ->getLimits($this->wallet);
  }

  /*
   * return the overridden value of all overridable currencies available to this wallet
   *
   * @return array
   *
   * saved limit overrides, keyed by curr_id. Each override is an array with
   * min, max, editor, & date(unixtime)
   */
  public function overrides() {
    $overridable_curr_ids = $this->overridable();
    if (empty($overridable_curr_ids)) {
      return [];
    }
    $rows = $this->database->select('mcapi_wallets_limits', 'l')
      ->fields('l', array('curr_id', 'max', 'value', 'editor', 'date'))
      ->condition('wid', $this->wallet->id())
      ->condition('curr_id', $overridable_curr_ids)
      ->execute()
      ->fetchAll();
    $result = [];
    foreach ($rows as $limit) {
      $key = $limit->max ? 'max' : 'min';
      $result[$limit->curr_id][$key] = [
        'value' => $limit->value,
        'editor' => $limit->editor,
        'date' => $limit->date
      ];
    }
    return $result;
  }

  private function overridable() {
    $overridable_curr_ids = [];
    foreach ($this->wallet->currenciesAvailable() as $currency) {
      $config = $currency->getThirdPartySettings('mcapi_limits');
      if (!empty($config['override'])) {
        $overridable_curr_ids[] = $currency->id();
      }
    }
    return $overridable_curr_ids;
  }

  static function create($wallet) {
    return \Drupal::service('mcapi_limits.wallet_limiter')->setwallet($wallet);
  }
}
