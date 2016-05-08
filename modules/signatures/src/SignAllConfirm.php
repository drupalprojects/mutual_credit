<?php

/**
 * @file
 * Contains \Drupal\mcapi_signatures\SignAllConfirm
 */

namespace Drupal\mcapi_signatures;

use \Drupal\Core\Form\ConfirmFormBase;
use \Drupal\mcapi_signatures\Signatures;
/**
 * Returns responses for Wallet routes.
 */
class SignAllConfirm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  function getQuestion() {

  }

  /**
   * {@inheritdoc}
   */
  function getCancelUrl() {

  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {

  }

  public function form() {
    debug('this page is untested');
    
    foreach (Signatures::transactionsNeedingSigOfUser($account) as $serial) {
      $unsigned[] = Transaction::loadBySerial($serial);
    }

    $form['preview'][] = \Drupal::entityTypeManager()
      ->getViewBuilder('mcapi_transaction')
      ->viewMultiple($unsigned, 'sentence');

    $form['account'] = array(
      '#type' => 'value',
      '#value' => $account
    );
    $form['buttons']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Sign all')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $values = $form_state->getValues();
    foreach (Signatures::transactionsNeedingSigOfUser($values['account']) as $serial) {
      $transaction = current(Transaction::loadBySerial($serial));
      Self::sign($transaction, $values['account']);
    }
    $transaction->save();
    drupal_set_message($message);
    $form_state->setRedirect('user.page');
  }

}
