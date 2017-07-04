<?php

namespace Drupal\mcapi_limits;

use Drupal\mcapi\Entity\CurrencyInterface;
use Drupal\mcapi\Entity\WalletInterface;
use Drupal\mcapi\Currency;

/**
 * Couldn't be bothered to make an interface for this.
 */
class WalletLimiter {

  /**
   * The wallet we are working with.
   */
  private $wallet;

  /**
   * An array keyed by currency ids each containing a min and a max value.
   *
   * Needed in every public function.
   */
  private $limits = [];

  /**
   * The database.
   */
  private $database;

  /**
   * Constructor.
   */
  public function __construct($database) {
    $this->database = $database;
  }

  /**
   * Create an instance of this Limiter.
   *
   * @note this isn't how most services use the create function.
   */
  public static function create($wallet) {
    return \Drupal::service('mcapi_limits.wallet_limiter')->setwallet($wallet);
  }

  /**
   * Populate this object with a wallet.
   */
  public function setWallet(WalletInterface $wallet) {
    $this->wallet = $wallet;
    $this->limits = $this->getLimits(TRUE);
    return $this;
  }

  /**
   * Prepare the $this->limits.
   */
  public function getLimits($refresh = TRUE) {
    if ($this->limits && !$refresh) {
      return $this->limits;
    }
    $limits = [];
    // Get the default limits.
    $needed_currencies = mcapi_currencies_available($this->wallet);
    foreach ($needed_currencies as $curr_id => $currency) {
      $limits[$curr_id] = $this->defaults($currency);
    }
    // Overwrite defaults with any per-wallet overridden values.
    foreach ($this->overrides() as $curr_id => $rows) {
      foreach ($rows as $limit => $vals) {
        $limits[$curr_id][$limit] = $vals['value'];
      }
    }

    // Then add in the currencies with no limits.
    foreach (array_diff_key($needed_currencies, $limits) as $curr_id => $currency) {
      $limits[$curr_id] = ['min' => NULL, 'max' => NULL];
    }
    return $limits;
  }

  /**
   * Get the raw max value of this wallet in this currency.
   *
   * @param string $curr_id
   *   The currency ID.
   *
   * @return int
   *   The raw value.
   */
  public function max($curr_id) {
    return $this->limits[$curr_id]['max'];
  }

  /**
   * Get the raw min value of this wallet in this currency.
   *
   * @param string $curr_id
   *   The currency ID.
   *
   * @return int
   *   The raw value.
   */
  public function min($curr_id) {
    return $this->limits[$curr_id]['min'];
  }

  /**
   * Get the difference between the given amount and the max limit.
   *
   * @param string $curr_id
   *   The currency ID.
   * @param int $balance
   *   The raw quantity value.
   *
   * @note the result is > 0 while the balance is greater than the min
   */
  public function spendLimit($curr_id, $balance) {
    $min = $this->min($curr_id);
    if (isset($min)) {
      return -$min - $balance;
    }
  }

  /**
   * Get the difference between the given amount, and the min limit.
   *
   * @param string $curr_id
   *   The currency ID.
   * @param int $balance
   *   The raw quantity value.
   *
   * @note the result is > 0 while the balance is less than the max
   */
  public function earnLimit($curr_id, $balance) {
    $max = $this->max($curr_id);
    if (isset($max)) {
      return $max - $balance;
    }
  }

  /**
   * Return this object as a string.
   */
  public function __toString() {
    foreach ($this->limits as $curr_id => $limits) {
      $currency = Currency::load($curr_id);
      $row = [];
      if (!is_null($limits['min'])) {
        $row[] = t('Min %quant', ['%quant' => $currency->format($limits['min'])]);
      }
      if (!is_null($limits['max'])) {
        $row[] = t('Max %quant', ['%quant' => $currency->format($limits['max'])]);
      }
      if ($row) {
        $output[] = $row;
      }
    }
    return implode(' | ', $output);
  }

  /**
   * Get the limits for the given currency.
   *
   * Per wallet overrides not considered.
   *
   * @param CurrencyInterface $currency
   *   The Currency entity.
   *
   * @return array
   *   The min and max limits.
   */
  public function defaults(CurrencyInterface $currency) {
    return \Drupal::service('plugin.manager.mcapi_limits')
      ->createInstanceCurrency($currency)
      ->getLimits($this->wallet);
  }

  /**
   * Return the overridden value of all available overridable currencies.
   *
   * @return array
   *   Mins and maxes, keyed by currency id. Each override is an array with
   *   min, max, editor, & date(unixtime).
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
        'date' => $limit->date,
      ];
    }
    return $result;
  }

  /**
   * Get the currency IDs which are overridable.
   *
   * @return array
   *   The currency IDs
   */
  private function overridable() {
    $overridable_curr_ids = [];
    foreach (mcapi_currencies_available($this->wallet) as $currency) {
      $config = $currency->getThirdPartySettings('mcapi_limits');
      if (!empty($config['override'])) {
        $overridable_curr_ids[] = $currency->id();
      }
    }
    return $overridable_curr_ids;
  }

}
