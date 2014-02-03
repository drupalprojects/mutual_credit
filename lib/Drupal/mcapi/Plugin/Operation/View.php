<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Operations\View
 *  View is a special operation because it does nothing except link
 */

namespace Drupal\mcapi\Plugin\Operation;

use Drupal\mcapi\OperationBase;
use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\CurrencyInterface;

/**
 * Links to the transaction certificate
 *
 * @Operation(
 *   id = "view",
 *   label = @Translation("View"),
 *   description = @Translation("Visit the transaction's page"),
 *   settings = {
 *     "weight" = "1",
 *     "sure" = ""
 *   }
 * )
 */
class View extends OperationBase {//does it go without saying that this implements OperationInterface

  /*
   * {@inheritdoc}
   */
  public function opAccess(TransactionInterface $transaction) {
    //you can view a transaction if you can view either the payer or payee wallets
    return $transaction->get('payer')->entity->access('view', $account)
    || $transaction->get('payee')->entity->access('view', $account);
  }

  /*
   * {@inheritdoc}
  */
  public function settingsForm(array &$form) {
    parent::settingsForm($form);
    unset($form['sure']['button'], $form['sure']['cancel_button'], $form['notify']);
    $newform = array('#tree' => 1);
    $newform['sure'] = $form['sure'];
    $newform['sure']['#type'] = 'container';
    $newform['op_title'] = $form['op_title'];
    $newform += $form['actions'];
    $form = $newform;
  }
}
