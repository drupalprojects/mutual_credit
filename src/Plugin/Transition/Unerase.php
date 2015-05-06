<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Transitions\Unerase
 *
 */

namespace Drupal\mcapi\Plugin\Transition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\CurrencyInterface;
use Drupal\mcapi\Entity\State;
use Drupal\mcapi\Entity\Transaction;
use Drupal\mcapi\Plugin\TransitionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Restore an erased transition
 *
 * @Transition(
 *   id = "unerase"
 * )
 */
class Unerase extends TransitionBase {

  /**
   * @see \Drupal\mcapi\TransitionBase::buildConfigurationForm()
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    return $form;
  }
  /**
   *  access callback for transaction transition 'erase'
   *  @return boolean
  */
  public function accessOp(TransactionInterface $transaction, AccountInterface $account) {
    if ($transaction->state->target_id == TRANSACTION_STATE_ERASED) {
      return parent::accessOp($transaction, $account);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
  */
  public function execute(TransactionInterface $transaction, array $context) {
    $store = \Drupal::service('keyvalue.database')->get('mcapi_erased');
    $transaction->set('state', $store->get($transaction->serial->value, 0));
    $store->delete($transaction->serial->value);

    return ['#markup' => $this->t('The transaction is restored.')];
  }

}
