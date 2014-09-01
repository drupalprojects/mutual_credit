<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Transitions\View
 *  View is a special transition because it does nothing except link
 */

namespace Drupal\mcapi\Plugin\Transition;

use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\mcapi\Entity\CurrencyInterface;
use Drupal\Core\Form\FormStateInterface;

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
  public function opAccess(TransactionInterface $transaction) {
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
