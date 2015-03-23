<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Transition\Create
 */

namespace Drupal\mcapi\Plugin\Transition;

use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\CurrencyInterface;
use Drupal\mcapi\McapiTransactionException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mcapi\Plugin\Transition2Step;
use Drupal\Core\Session\AccountInterface;

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
   * A transaction can be created if the user has a wallet, and permission to transaction
   */
  public function opAccess(TransactionInterface $transaction, AccountInterface $acount) {
    //TODO check that the use is allowed to pay in to and out from at least one wallet each.
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
