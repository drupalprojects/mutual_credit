<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Transitions\Erase
 *
 */

namespace Drupal\mcapi\Plugin\Transition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\CurrencyInterface;
use Drupal\mcapi\Entity\State;
use Drupal\mcapi\Entity\Transaction;
use Drupal\mcapi\Plugin\Transition2Step;
use Drupal\Core\Session\AccountInterface;

/**
 * Undo transition
 *
 * @Transition(
 *   id = "erase"
 * )
 */
class Erase extends Transition2Step {

  /**
   * @see \Drupal\mcapi\TransitionBase::buildConfigurationForm()
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    unset($form['access'][TRANSACTION_STATE_ERASED]);
    return $form;
  }
  /**
   *  access callback for transaction transition 'erase'
   *  @return boolean
  */
  public function accessOp(TransactionInterface $transaction, AccountInterface $account) {
    if ($transaction->state->target_id != TRANSACTION_STATE_ERASED) {
      return parent::accessOp($transaction, $account);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
  */
  public function execute(TransactionInterface $transaction, array $context) {

    $key_value_store = \Drupal::service('keyvalue.database')
      ->get('mcapi_erased')
      ->set($transaction->serial->value, $transaction->state->target_id);

    $transaction->set('state', TRANSACTION_STATE_ERASED);//will be saved later

    return ['#markup' => $this->t('The transaction is erased.')];
  }

}
