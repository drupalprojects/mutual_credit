<?php

/**
 * @file
 * Contains \Drupal\mcapi\CurrencyInterface.
 */

namespace Drupal\mcapi;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an interface defining a currency configuration entity.
 */
interface CurrencyInterface extends ConfigEntityInterface {

  /**
   * returns the currency label
   *
   * @return string
   */
  public function label($langcode = NULL);

  /**
   * return the number of transactions, in all states
   *
   * @param array $conditions
   *   an array of conditions to meet, keyed by mcapi_entity property name
   * @param boolean $serial
   *   whether to return the number of serials, or the number of xids
   *
   * @return integer.
   */
  public function transactions(array $conditions = array(), $serial = FALSE);

  /**
   * return the sum of all transactions, in all states
   *
   * @param array $conditions
   *   an array of conditions to meet, keyed by mcapi_entity property name
   *
   * @return integer
   *   raw quantity which should be formatted using currency->format($value);
   */
  public function volume(array $conditions = array());

  /**
   * check that a currency has no transactions and if so, call the parent delete method
   */
  public function delete();

  /**
   * Format the database integer value according to the formatting string in $currency->format.
   *
   * @param integer $raw_num
   *   the value stored in the worth field 'value' column
   *
   * @return string
   *   #markup containing the formatted value
   */
  public function format($raw_num);

  /**
   * Format the value in some other way
   * This was created for google charts which wouldn't understand 'CC1.23' as a number
   *
   * @param integer $raw_num
   *   the value stored in the worth field 'value' column
   *
   * @return string
   *   plaintext #markup containing the formatted value. Hopefully 90 mins, normally formatted
   *   say as '1 1/2 hours' would come out of this function '1.30'. This is good for display, but
   *   will produce unexpected results if used in client side calculations. Try to avoid
   *   calculating with formatted strings in base 60 on the client side.
   */
  public function faux_format($raw_num, $format = array());

  /**
   * Load currencies for a given wallet
   * A list of all the currencies in active exchanges of which the user is a member.
   *
   * @param AccountInterface $account
   *
   * @return array
   *   of currencies
   */
  public static function user(AccountInterface $account = NULL);

  /**
   * Check whether it is allowed to deleted this currency, which means deleting all transactions in it
   *
   * @return boolean
   *
   * @todo there could be a setting 'delete currencies with undeleted transactions';
   */
  public function deletable();

  /**
   * break up a native quantity into the parts
   * @param integer
   *   the stored value
   * @return array
   *   the value expressed in parts, e.g. pounds, shillings and pence
   */
  public function formatted_parts($raw_num);

}

