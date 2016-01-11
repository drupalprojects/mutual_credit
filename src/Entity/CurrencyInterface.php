<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\CurrencyInterface.
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an interface defining a currency configuration entity.
 */
interface CurrencyInterface extends ConfigEntityInterface {

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
  function transactionCount(array $conditions = []);

  /**
   * return the sum of all transactions, in all states
   *
   * @param array $conditions
   *   an array of conditions to meet, keyed by mcapi_entity property name
   *
   * @return integer
   *   raw quantity which should be formatted using currency->format($value);
   */
  function volume(array $conditions = []);

  /**
   * Format the database integer value according to the formatting string in $currency->format.
   *
   * @param integer $raw_num
   *   the value stored in the worth field 'value' column
   *
   * @return string
   *   #markup containing the formatted value
   */
  function format($raw_num);

  /**
   * Check whether it is allowed to deleted this currency, which means deleting
   * all transactions in it
   *
   * @return boolean
   */
  function deletable();

  /**
   * break up a native quantity into the parts
   * @param integer
   *   the stored value
   * @return array
   *   the value expressed in parts, e.g. pounds, shillings and pence
   */
  function formattedParts($raw_num);

}

