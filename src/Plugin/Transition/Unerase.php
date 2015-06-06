<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Transitions\Unerase
 *
 */

namespace Drupal\mcapi\Plugin\Transition;

use Drupal\Core\Form\FormStateInterface;
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
   *  access callback for transaction transition 'erase'
   *  @return boolean
  */
  public function accessOp(AccountInterface $account) {
    if ($this->transaction->state->target_id == TRANSACTION_STATE_ERASED) {
      return parent::accessOp($account);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
  */
  public function execute(array $context) {
    $store = \Drupal::service('keyvalue.database')->get('mcapi_erased');
    $this->transaction->set('state', $store->get($this->transaction->serial->value, 0));
    $store->delete($this->transaction->serial->value);
    $saved = $this->transaction->save();

    return ['#markup' => $this->t('The transaction is restored.')];
  }

}
