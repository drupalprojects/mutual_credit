<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Transitions\Delete
 *
 */

namespace Drupal\mcapi\Plugin\Transition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\mcapi\Entity\CurrencyInterface;
use Drupal\mcapi\Entity\State;

/**
 * Undo transition
 *
 * @Transition(
 *   id = "delete",
 *   label = @Translation("Delete"),
 *   description = @Translation("Remove the transaction from the database"),
 *   settings = {
 *     "weight" = "3",
 *     "sure" = "Are you sure you want to remove the transaction completely?"
 *   }
 * )
 */
class Delete extends Transition2Step {

  /**
   * @see \Drupal\mcapi\TransitionBase::buildConfigurationForm()
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    unset($form['access'][TRANSACTION_STATE_ERASED]);
    //@todo check the form hasn't changed in Drupal\mcapi\TransitionBase::buildConfigurationForm()
    //if the transaction no longer exists there's nothing to configure for the final step
    unset(
      $form['feedback']['format2'],
      $form['feedback']['twig2'],
      $form['feedback']['redirect']['#states']
    );
    //because after a transaction is deleted, you can't very well go and visit it.
    $form['feedback']['redirect']['#required'] = TRUE;

    return $form;
  }

  /**
   * {@inheritdoc}
  */
  public function execute(TransactionInterface $transaction, array $context) {

    $violations = $transaction->delete();

    if ($violations) {
      throw new McapiTransactionException('', implode('. ', $violations));
    }

    return array('#markup' => $this->t('The transaction is deleted.'));
  }

}
