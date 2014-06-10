<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Transition\TransitionInterface.
 */

namespace Drupal\mcapi\Plugin\Transition;

use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\mcapi\McapiTransactionException;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

interface TransitionInterface extends ConfigurablePluginInterface, PluginFormInterface {

  /**
   * inject something into the transition form
   * the values will be passed into the transition execute function
   *
   * @param TransactionInterface $transaction
   * @param ConfigFactory $config
   *
   * @return array
   *   FormAPI $elements
   */
	public function form(TransactionInterface $transaction);

	/**
	 * Access control function to determine whether this
	 * transition can be performed on a given transaction
	 *
	 * @param TransactionInterface $transaction
	 *
   * @return Boolean
   *   TRUE if access is granted
	 */
  public function opAccess(TransactionInterface $transaction);

  /**
   * Do the actual transition on the passed transaction, and return some html
   * The method in the base class handles the mail notifications
   *
   * @param TransactionInterface $transaction
   *   A transaction entity object
   *
   * @param array $context
   *   the $form_state 'values', the plugin 'config', the transation 'old_state'
   *
   * @return string
   *   an html snippet for the new page, or which in ajax mode replaces the form
   *
   * @throws McapiTransactionException
   */
  public function execute(TransactionInterface $transaction, array $context);


  /**
   * Execute ajax submission of the transition form, delivering ajax commands to the browser.
   * then the function exits;
   *
   * @param array $form_state_values
   *   the contents of $form_state['values']
   */
  public function ajax_submit(array $form_state_values);

}