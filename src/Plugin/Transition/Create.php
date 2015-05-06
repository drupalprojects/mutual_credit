<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Transition\Create
 */

namespace Drupal\mcapi\Plugin\Transition;

use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\CurrencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mcapi\Plugin\TransitionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Create transition
 *
 * @Transition(
 *   id = "create"
 * )
 */
class Create extends TransitionBase {

  /**
   * {@inheritdoc}
   * A transaction can be created if the user has a wallet, and permission to transaction
   */
  public function accessOp(TransactionInterface $transaction, AccountInterface $acount) {
    //@todo check that the user is allowed to pay in to payee wallet AND out from payer_wallet.
    mtrace();
    return empty($transaction->get('xid')->value);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(TransactionInterface $transaction, array $context) {
    //the save operation takes place elsewhere
    return ['#markup' => t('Transaction created')];
  }

  /**
   * {@inheritdoc}
  */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    unset($form['title']);//because this transition never appears as a link.
    return $form;
  }


  final public function accessState(TransactionInterface $transaction, AccountInterface $account) {
    //can we payin to the payee wallet and payout of the payer wallet
    return $transaction->payer->entity->access('payout')
    || $transaction->payee->entity->access('payin');
  }
}
