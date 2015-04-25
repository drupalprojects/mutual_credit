<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Transitions\View
 *  View is a special transition because it does nothing except link
 */

namespace Drupal\mcapi\Plugin\Transition;

use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\CurrencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\mcapi\Plugin\TransitionBase;

/**
 * Links to the transaction certificate
 *
 * @Transition(
 *   id = "view",
 *   label = @Translation("View"),
 *   description = @Translation("Visit the transaction's page"),
 *   settings = {
 *     "weight" = "1",
 *     "sure" = ""
 *   }
 * )
 */
class View extends TransitionBase {//does it go without saying that this implements TransitionInterface

  /*
   * {@inheritdoc}
   */
  public function accessOp(TransactionInterface $transaction, AccountInterface $account) {
    //you can view a transaction if you can view either the payer or payee wallets
    return $transaction->payer->entity->access('details')
    || $transaction->payee->entity->access('details');
  }


  /*
   * {@inheritdoc}
  */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['sure']['#title'] = t('Display page');
    return $form;
  }

  public function execute(TransactionInterface $transaction, array $context){}

}
