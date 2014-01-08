<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Operations\Confirm
 */

namespace Drupal\mcapi\Plugin\Operation;

use Drupal\mcapi\OperationBase;
use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\CurrencyInterface;
use \Drupal\Core\Config\ConfigFactory;

/**
 * Confirm operation
 *
 * @Operation(
 *   id = "confirm",
 *   label = @Translation("Confirm"),
 *   description = @Translation("Confirm a new transaction"),
 *   settings = {
 *     "weight" = "0",
 *     "sure" = "Are you sure?"
 *   }
 * )
 */
class Confirm extends OperationBase {

  /*
   * {@inheritdoc}
   */
  public function access_form(CurrencyInterface $currency) {
    return array();
  }

  /*
   * {@inheritdoc}
   */
  public function opAccess(TransactionInterface $transaction) {
    //this affects the link visibility and the page access
    return empty($transaction->serial->value);
  }

  /*
   * Save the transaction
  *
  * @param TransactionInterface $transaction
  *   A transaction entity object
  * @param array $values
  *   the contents of $form_state['values']
  *
  * @return string
  *   an html snippet for the new page, or which in ajax mode replaces the form
  */
  public function execute(TransactionInterface $transaction, array $values) {
    $message = t('//TODO: save the transaction!');
    return array('#markup' => $message);
  }

  /*
   * {@inheritdoc}
  */
  public function settingsForm(array &$form, ConfigFactory $config) {
    parent::settingsForm($form, $config);
    unset(
      $form['sure']['button'],
      $form['sure']['cancel_button'],
      $form['notify'],
      $form['op_title']
    );
  }

}
