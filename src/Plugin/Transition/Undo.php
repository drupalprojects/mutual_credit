<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Transitions\Undo
 *
 */

namespace Drupal\mcapi\Plugin\Transition;

use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\mcapi\Entity\CurrencyInterface;
use Drupal\mcapi\Entity\State;

/**
 * Undo transition
 *
 * @Transition(
 *   id = "undo",
 *   label = @Translation("Undo"),
 *   description = @Translation("Undo, according to global undo mode"),
 *   settings = {
 *     "weight" = "3",
 *     "sure" = "Are you sure you want to undo?"
 *   }
 * )
 */
class Undo extends TransitionBase {

  /**
   * @see \Drupal\mcapi\TransitionBase::buildConfigurationForm()
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    //@todo check the form hasn't changed in Drupal\mcapi\TransitionBase::buildConfigurationForm()
    unset(
      $form['feedback']['format2'],
      $form['feedback']['twig2'],
      $form['feedback']['redirect']['#states']
    );
    //because after a transaction is deleted, you can't very well go and visit it.
    $form['feedback']['redirect']['#required'] = TRUE;

    $form['access'] = array(
      '#title' => t('Access control'),
      '#description' => t('Who can undo transactions in each state?'),
      '#type' => 'details',
      '#tree' => TRUE,
      '#collapsible' => TRUE,
      '#open' => FALSE,
      '#weight' => 8,
    );
    //TODO would be really nice if this was in a grid
    foreach (State::loadMultiple() as $state) {
      if ($state->id == 'undone') continue;
      $form['access'][$state->id] = array (
        '#title' => $state->label,
        '#description' => $state->description,
        '#type' => 'checkboxes',
        '#options' => array(
      	  'payer' => t('Owner of payer wallet'),
          'payee' => t('Owner of payee wallet'),
          'creator' => t('Creator of the transaction'),
          'helper' => t('An exchange helper'),
          'admin' => t('The super admin')
          //its not elegant for other modules to add options
        ),
        '#default_value' => $this->configuration['access'][$state->id],
        '#weight' => $this->configuration['weight']
      );
    }
    return $form;
  }
  /**
   *  access callback for transaction transition 'view'
   *  @return boolean
  */
  public function opAccess(TransactionInterface $transaction) {
    if ($transaction->get('state')->value != TRANSACTION_STATE_UNDONE) {
      $account = \Drupal::currentUser();
      $options = array_filter($this->configuration['access']);
      if (!array_key_exists($transaction->state->target_id, $options)) {
        //this means the op hasn't been configured since a new state was added.
        //TODO review the idea that undo access should be according to state.
        if (\Drupal::currentUser()->id() == 1) {
          drupal_set_message(
            t('Please resave the undo op, paying attention to access controls: !link', array('!link' => l('admin/accounting/workflow/undo', 'admin/accounting/workflow/undo'))),
            'warning'
          );
        }
        return FALSE;
      }
      foreach ($options[$transaction->state->target_id] as $option) {
        switch ($option) {
        	case 'helper':
        	  if ($account->hasPermission('exchange helper')) return TRUE;
        	  continue;
        	case 'admin':
        	  if ($account->hasPermission('manage mcapi')) return TRUE;
        	  continue;
        	case 'payer':
        	case 'payee':
        	  $wallet = $transaction->{$option}->entity;
        	  $parent = $$wallet->getOwner();
        	  if ($parent && $wallet->pid->value == $account->id() && $parent->getEntityTypeId() == 'user') {
        	    return TRUE;
        	  }
        	  continue;
        	case 'creator':
        	  return $transaction->creator->target_id == $account->id();
        }
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
  */
  public function execute(TransactionInterface $transaction, array $context) {

    $violations = $transaction->delete();

    if ($violations) {
      throw new McapiTransactionException('', implode('. ', $violations));
    }
    parent::execute($transaction, $context);

    return array('#markup' => t('The transaction is undone.'));
  }

}
