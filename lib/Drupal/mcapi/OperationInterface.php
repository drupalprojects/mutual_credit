<?php

/**
 * @file
 * Contains \Drupal\mcapi\OperationInterface.
 */

namespace Drupal\mcapi;

use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\CurrencyInterface;
use Drupal\Core\Config\ConfigFactory;

//TODO Gordon should we extend EntityInterface or ConfigEntityInterface?
interface OperationInterface {

  /**
   * inject something into the operation form
   * the values will be passed into the operation execute function
   *
   * @param TransactionInterface $transaction
   * @param ConfigFactory $config
   *
   * @return array
   *   FormAPI $elements
   */
	public function form(TransactionInterface $transaction);

 /**
	* default form for configuring access to an operations for a currency
	* offers a checkbox list of the transaction_operation_access callbacks
	*
	* @param CurrencyInterface $currency
	*
	* @return array $element
	*   FormAPI $elements
	*/
	public function access_form(CurrencyInterface $currency);

	/**
	 * Access control function to determine whether this
	 * operation can be performed on a given transaction
	 *
	 * @param TransactionInterface $transaction
	 *
   * @return Boolean
   *   TRUE if access is granted
	 */
  public function opAccess(TransactionInterface $transaction);

  /**
   * Do the actual operation on the passed transaction, and return some html
   * The method in the base class handles the mail notifications
   *
   * @param TransactionInterface $transaction
   *   A transaction entity object
   * @param array $values
   *   the contents of $form_state['values']
   *
   * @return string
   *   an html snippet for the new page, or which in ajax mode replaces the form
   */
  public function execute(TransactionInterface $transaction, array $values);

  /**
   * operation settings form which individual operations can alter
   * as distinct from the OperationBase::access_form()
   *
   * @param CurrencyInterface $currency
   *   a currency configuration entity
   *
   * @return array $element
   *   FormAPI $elements
   */
  public function settingsForm(array &$form);

  /**
   * Execute ajax submission of the operation form, delivering ajax commands to the browser.
   * then the function exits;
   *
   * @param array $form_state_values
   *   the contents of $form_state['values']
   */
  public function ajax_submit(array $form_state_values);

}