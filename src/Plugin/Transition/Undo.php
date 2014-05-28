<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Transitions\Undo
 *
 */

namespace Drupal\mcapi\Plugin\Transition;

use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\mcapi\Entity\CurrencyInterface;

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
   * @see \Drupal\mcapi\TransitionBase::settingsForm()
   */
  public function settingsForm(array &$form) {
    parent::settingsForm($form);
    //@todo check the form hasn't changed in Drupal\mcapi\TransitionBase::settingsForm()
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
    foreach (entity_load_multiple('mcapi_state') as $state) {
      if ($state->id == 'undone') continue;
      $form['access'][$state->id] = array (
        '#title' => $state->label,
        '#description' => $state->description,
        '#type' => 'checkboxes',
        '#options' => array(
      	  'payer' => t('Owner of payer wallet'),
          'payee' => t('Owner of payee wallet'),
          'helper' => t('An exchange helper'),
          'admin' => t('The super admin')
        ),
        '#default_value' => $this->settings['access'][$state->id],
        '#weight' => $this->settings['weight']
      );
    }
  }
  /**
   *  access callback for transaction transition 'view'
  */
  public function opAccess(TransactionInterface $transaction) {
    if ($transaction->get('state')->value != TRANSACTION_STATE_UNDONE) {
      $account = \Drupal::currentUser();
      $options = array_filter($this->settings['access']);
      foreach ($options[$transaction->get('state')->value] as $option) {
        switch ($option) {
        	case 'helper':
        	  if ($account->hasPermission('exchange helper')) return TRUE;
        	  continue;
        	case 'admin':
        	  if ($account->hasPermission('manage mcapi')) return TRUE;
        	  continue;
        	case 'payer':
        	case 'payee':
        	  $parent = $transaction->get($option)->getValue(TRUE)->getparent();
        	  if ($parent && $parent->get('pid')->value == $account->id && $parent->getEntityTypeId() == 'user') return TRUE;
        	  continue;
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
