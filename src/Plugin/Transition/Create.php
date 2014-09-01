<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Transitions\Create
 */

namespace Drupal\mcapi\Plugin\Transition;

use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\mcapi\Entity\CurrencyInterface;
use Drupal\mcapi\McapiTransactionException;
use Drupal\Core\Form\FormStateInterface;

/**
 * Create transition
 *
 * @Transition(
 *   id = "create",
 *   label = @Translation("Create"),
 *   description = @Translation("Create a new transaction"),
 *   settings = {
 *     "weight" = "0",
 *     "sure" = "Are you sure?"
 *   }
 * )
 */
class Create extends Transition2Step {

  /**
   * {@inheritdoc}
   */
  public function opAccess(TransactionInterface $transaction) {
    //this affects the link visibility and the page access
    //transaction can be created by anyone as long as it hasn't yet been saved
    return empty($transaction->get('xid')->value);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(TransactionInterface $transaction, array $context) {
    //this transaction came from the tempstore and was validated in step one
    $status = $transaction->save();
    if ($status != SAVED_NEW) {
      throw new McapiTransactionException('', t('Failed to save transaction'));
    }

    return array(t('Transaction created'));
  }

  /**
   * {@inheritdoc}
  */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    unset($form['title']);//because this transition never appears as a link.
    return $form;
  }

}
