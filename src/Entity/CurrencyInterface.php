<?php

namespace Drupal\mcapi\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a currency configuration entity.
 */
interface CurrencyInterface extends ConfigEntityInterface {

  const TYPE_ACKNOWLEDGEMENT = 2;
  const TYPE_ASSET = 1;
  const TYPE_PROMISE = 0;

  // The raw integer value held in the database e.g.5400.
  const DISPLAY_NATIVE = 1;
  // The text value for display eg 1hr 30 mins.
  const DISPLAY_NORMAL = 2;
  // Formatted to appear as a number but without e.g 1.30.
  const DISPLAY_PLAIN = 3;

  /**
   * Return the number of transactions, in all states.
   *
   * @param array $conditions
   *   An array of conditions to meet, keyed by mcapi_entity property name.
   *
   * @return integer.
   *   The number of transactions.
   */
  public function transactionCount(array $conditions = []);

  /**
   * Return the sum of all transactions, in all states.
   *
   * @param array $conditions
   *   An array of conditions to meet, keyed by mcapi_entity property name.
   *
   * @return int
   *   Raw quantity which should be formatted using currency->format($value).
   */
  public function volume(array $conditions = []);

  /**
   * Format the database integer value according to the formatting string.
   *
   * @param int $raw_num
   *   The value stored in the worth field 'value' column.
   *
   * @return MarkupInterface
   *   Markup containing the formatted value.
   */
  public function format($raw_num);

  /**
   * Get the moment the currency was first used.
   *
   * @return int
   *   The unixtime of the first transaction creation.
   */
  public function firstUsed();

  /**
   * Determine whether it is allowed to deleted this currency.
   *
   * @return bool
   *   TRUE if the currency is deleted at this time by the current user.
   *
   * @note deleting means deleting all transactions using this currency.
   *
   * @todo shouldn't this be in an access controller?
   */
  public function deletable();

  /**
   * Break up a native quantity into the parts.
   *
   * @param int $raw_num
   *   The stored value.
   *
   * @return array
   *   The value expressed in parts, e.g. pounds, shillings and pence.
   */
  public function formattedParts($raw_num);

}
