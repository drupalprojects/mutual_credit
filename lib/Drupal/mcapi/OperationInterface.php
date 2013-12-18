<?php

/**
 * @file
 * Contains \Drupal\mcapi\OperationInterface.
 */

namespace Drupal\mcapi;

use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\CurrencyInterface;
use Drupal\Core\Config\ConfigFactory;

//TODO should we extend EntityInterface or ConfigEntityInterface?
interface OperationInterface {

	public function operation_form(TransactionInterface $transaction);

	/*
	 * produces a form element for conifiguring access to this operation
	 */
	public function access_form(CurrencyInterface $currency);

	/*
	 * Determines whether this operation can be performed on this transaction
	*/
  public function opAccess(TransactionInterface $transaction);

	/*
	 * Performs the operation and returns a render array
	*/
  public function execute(TransactionInterface $transaction, array $values);

  public function settingsForm(array &$form, ConfigFactory $config);

  public function ajax_submit(array $form_state_values);

  public function confirm_form(array $form, array &$form_state, $op);
}