<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Operations\Create
 */

namespace Drupal\mcapi\Plugin\Operation;

use Drupal\mcapi\OperationBase;
use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\CurrencyInterface;
use \Drupal\Core\Config\ConfigFactory;

/**
 * Create operation
 *
 * @Operation(
 *   id = "create",
 *   label = @Translation("Create"),
 *   description = @Translation("Create a new transaction"),
 *   settings = {
 *     "weight" = "0",
 *     "sure" = "Are you sure?"
 *   }
 * )
 */
class Create extends OperationBase {

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
    return empty($transaction->get('xid')->value);
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
    try {
      //was already validated
      $status = $transaction->save();
    }
    catch (Exception $e) {
      \Drupal::formBuilder()->setErrorByName(
        'actions',
        t("Failed to save transaction: @message", array('@message' => $e->getMessage))
      );
    }

    if ($status == SAVED_UPDATED) {
      $message = t("'%label' has been updated.", array('%label' => $transaction->label()));
    }
    else {
      $message = t("'%label' has been added.", array('%label' => $transaction->label()));
    }

    return array('#markup' => $message);
  }

  /*
   * {@inheritdoc}
  */
  public function settingsForm(array &$form) {
    parent::settingsForm($form);
    //unset(
      //$form['sure']['button'],
      //$form['sure']['cancel_button'],
      //$form['notify'],
      //$form['op_title']
    //);
  }

}
