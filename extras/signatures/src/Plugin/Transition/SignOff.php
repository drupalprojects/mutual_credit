<?php

/**
 * @file
 *  Contains Drupal\mcapi_signatures\Plugin\Transition\SignOff
 *  @todo This needs to be finished. Might want to inherit some things from the Sign transition
 */

namespace Drupal\mcapi_signatures\Plugin\Transition;

use Drupal\mcapi\Plugin\TransitionBase;
use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\CurrencyInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Form\FormStateInterface;

/**
 * Sign Off transition
 *
 * @Transition(
 *   id = "sign_off"
 * )
 */
class SignOff extends TransitionBase {

  /*
   * {@inheritdoc}
  */
  public function execute(TransactionInterface $transaction, array $values) {

    foreach ($transaction->signatures as $uid => $signed) {
      if ($signed) continue;
      transaction_sign($transaction, User::load($uid));
    }
    return [
      '#markup' => t(
        '@transaction is signed off',
        ['@transaction' => $transaction->label()]
      )
    ];
  }

  /**
   * @see \Drupal\mcapi\TransitionBase::buildConfigurationForm()
   * Ensure the pending checkbox is ticked
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['states'][TRANSACTION_STATE_PENDING]['#value'] = TRUE;
    $form['states'][TRANSACTION_STATE_PENDING]['#disabled'] = TRUE;
    $form['states'][TRANSACTION_STATE_FINISHED]['#value'] = FALSE;
    $form['states'][TRANSACTION_STATE_FINISHED]['#disabled'] = TRUE;
    return $form;
  }

  /*
   * {@inheritdoc}
  */
  public function accessOp(TransactionInterface $transaction, AccountInterface $account) {
    //Must be a valid transaction relative AND a named signatory.
    return parent::accessOp($transaction, $account)
      && isset($transaction->signatures)
      && is_array($transaction->signatures)// signatures property is populated
      && array_key_exists(\Drupal::currentUser()->id(), $transaction->signatures)//the current user is a signatory
      && !$transaction->signatures[\Drupal::currentUser()->id()];//the currency user hasn't signed
  }

}
