<?php

namespace Drupal\mcapi_signatures;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\mcapi\Storage\TransactionStorage;

/**
 * Confirm form to add all signatures needed by a user.
 *
 * @todo complete this
 */
class SignAllConfirm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {

  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {

  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {

  }

  /**
   * {@inheritdoc}
   */
  public function form() {
    debug('this page is untested. Whose signatures are we signing? CurrentUser?');
    $account = \Drupal::service('current_user');

    foreach (Signatures::transactionsNeedingSigOfUser($account) as $serial) {
      $unsigned[] = TransactionStorage::loadBySerial($serial);
    }

    $form['preview'][] = \Drupal::entityTypeManager()
      ->getViewBuilder('mcapi_transaction')
      ->viewMultiple($unsigned, 'sentence');

    $form['account'] = array(
      '#type' => 'value',
      '#value' => $account,
    );
    $form['buttons']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Sign all'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    foreach (Signatures::transactionsNeedingSigOfUser($values['account']) as $serial) {
      $transaction = current(TransactionStorage::loadBySerial($serial));
      Self::sign($transaction, $values['account']);
    }
    $transaction->save();
    drupal_set_message(t('All the transactions have been signed.'));
    $form_state->setRedirect('user.page');
  }

}
