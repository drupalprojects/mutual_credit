<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Operations\Undo
 *
 */

namespace Drupal\mcapi\Plugin\Operation;

use Drupal\mcapi\OperationBase;
use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\CurrencyInterface;

/**
 * Undo operation
 *
 * @Operation(
 *   id = "undo",
 *   label = @Translation("Undo"),
 *   description = @Translation("Undo, according to global undo mode"),
 *   settings = {
 *     "weight" = "3",
 *     "sure" = "Are you sure you want to undo?"
 *   }
 * )
 */
class Undo extends OperationBase {

  /**
   * @see \Drupal\mcapi\OperationBase::settingsForm()
   */
  public function settingsForm(array &$form) {
    parent::settingsForm($form);
    //@todo check the form hasn't changed in Drupal\mcapi\OperationBase::settingsForm()
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
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#weight' => 8,
    );
    foreach (mcapi_get_states() as $state) {
      if ($state->id == 'undone') continue;
      $form['access'][$state->value] = array (
        '#title' => $state->label,
        '#description' => $state->description,
        '#type' => 'checkboxes',
        '#options' => array(
      	  'payer' => t('Owner of payer wallet'),
          'payee' => t('Owner of payee wallet'),
          'manager' => t('The exchange manager'),
          'helper' => t('An exchange helper'),
          'admin' => t('The super admin')
        ),
        '#default_value' => $this->config->get('access.'.$state->value),
        '#weight' => $this->weight
      );
    }
  }
  /**
   *  access callback for transaction operation 'view'
  */
  public function opAccess(TransactionInterface $transaction) {
    if ($transaction->get('state')->value == TRANSACTION_STATE_UNDONE) RETURN FALSE;
    $uid = \Drupal::currentUser()->id();
    $exchange = entity_load('mcapi_exchange', $transaction->get('exchange')->value);
    $options = array_filter($this->config->get('access'));
    foreach ($options[$transaction->get('state')->value] as $option) {
      switch ($option) {
      	case 'manager':
      	  if ($exchange->isManager()) return TRUE;
      	  continue;
      	case 'helper':
      	  if (\Drupal::currentUser()->hasPermission('exchange helper')) return TRUE;
      	  continue;
      	case 'admin':
      	  if (\Drupal::currentUser()->hasPermission('manage mcapi')) return TRUE;
      	  continue;
      	case 'payer':
      	case 'payee':
      	  $parent = $transaction->get($option)->getValue(TRUE)->getparent();
      	  if ($parent && $parent->get('pid')->value == $account->id && $parent->entityType() == 'user') return TRUE;
      	  continue;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
  */
  public function execute(TransactionInterface $transaction, array $values) {
    $transaction->delete();
    $message = t('The transaction is undone.') .' ';
    return array('#markup' => $message);
  }

}
