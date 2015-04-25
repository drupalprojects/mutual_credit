<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\TransitionInterface.
 * 
 * @todo update this interface 
 */

namespace Drupal\mcapi\Plugin;

use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\McapiTransactionException;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormStateInterface;

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
  public function accessOp(TransactionInterface $transaction, AccountInterface $account);

  /**
   * Do the actual transition on the passed transaction, and return some html
   * The method in the base class handles the mail notifications
   *
   * @param TransactionInterface $transaction
   *
   * @param array $context
   *   the $form_state->values, the plugin 'config', the transation 'old_state'
   *
   * @return array
   *   a renderable array
   *
   * @throws \Exception
   */
  public function execute(TransactionInterface $transaction, array $context);


  /**
   * Execute ajax submission of the transition form, delivering ajax commands to the browser.
   * then the function exits;
   *
   * @param FormStateInterface $form_state
   * 
   * @todo ajax_submit is currently unused
   */
  public function ajax_submit(FormStateInterface $form_state);

}