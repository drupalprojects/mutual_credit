<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Operations\Sign
 *
 */

namespace Drupal\mcapi_signatures\Plugin\Operation;

use Drupal\mcapi\OperationBase;
use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\CurrencyInterface;

/**
 * Sign operation
 *
 * @Operation(
 *   id = "sign",
 *   label = @Translation("Sign"),
 *   description = @Translation("Sign a pending transaction"),
 *   settings = {
 *     "weight" = "2",
 *     "sure" = "Are you sure you want to sign?"
 *   }
 * )
 */
class Sign extends OperationBase {

  /*
   * {@inheritdoc}
  */
  public function execute(TransactionInterface $transaction, array $values) {

    transaction_sign($serial, $uid);

    if ($transaction->state->value == TRANSACTION_STATE_FINISHED) {
      $message = t('@transaction is signed off', array('@transaction' => $transaction->label()));
    }
    else{
      $message = \Drupal::TranslationManager()->format_plural($num, '1 signature remaining', '@count signatures remaining');
    }

    parent::execute($transaction, $values);

    return array('#markup' => $message);
  }

  /*
   * {@inheritdoc}
  */
  public function opAccess(TransactionInterface $transaction) {
    if ($transaction->state->value == TRANSACTION_STATE_PENDING
      && array_key_exists('signatures', $transaction)
      && is_array($transaction->signatures)
      && array_key_exists(\Drupal::currentUser()->id(), $transaction->signatures)
      && $transaction->signatures[\Drupal::currentUser()->id()] == ''
    ) return TRUE;
    return FALSE;
  }

  /*
   * {@inheritdoc}
  */
  public function settingsForm(array &$form) {

    $form['countersignatories'] = array(
      '#title' => t('Counter-signatories'),
      '#description' => 'Other users required to sign the transaction',
      '#type' => 'entity_reference',
      //@todo this is really hard to do per-exchange.
      //would be nice to be able to save operation settings per-exchange...
      //in the mean time countersignatories should be chosen from all people with 'manage mcapi' permission
      '#options' => array(),
      '#default_value' => $this->config['countersignatories'],
      '#weight' => 3,
      '#multiple' => TRUE,
      '#required' => FALSE,
      '#states' => array(
        'visible' => array(
          ':input[name="special[send]"]' => array('checked' => TRUE)
        )
      )
    );
    parent::settingsForm($form);
    return $form;
  }
}
