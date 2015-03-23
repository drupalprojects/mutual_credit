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
use Drupal\mcapi\Plugin\Transition2Step;
use Drupal\Core\Session\AccountInterface;

/**
 * Undo transition
 *
 * @Transition(
 *   id = "erase",
 *   label = @Translation("Erase"),
 *   description = @Translation("Mark the transaction deleted"),
 *   settings = {
 *     "weight" = "3",
 *     "sure" = "Really erase this transaction?"
 *   }
 * )
 */
class Erase extends Transition2Step {

  /**
   * @see \Drupal\mcapi\TransitionBase::buildConfigurationForm()
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    unset($form['access'][TRANSACTION_STATE_ERASED]);
    debug($form['access']);
    return $form;
  }
  /**
   *  access callback for transaction transition 'erase'
   *  @return boolean
  */
  public function opAccess(TransactionInterface $transaction, AccountInterface $account) {
    if ($transaction->state->target_id != TRANSACTION_STATE_ERASED) {
      return parent::opAccess($transaction, $account);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
  */
  public function execute(TransactionInterface $transaction, array $context) {
    $message = [];
    foreach ($transaction->erase() as $exception) {
      $message[] = $exception->getMessage();
    }
    if ($message) {
      throw new McapiTransactionException('', implode('. ', $message));
    }

    return array('#markup' => $this->t('The transaction is erased.'));
  }

}
