<?php

/**
 * @file
 *  Contains Drupal\mcapi_signatures\Plugin\Transition\SignOff
 *  @todo This needs to be finished. Might want to inherit some things from the Sign transition
 */

namespace Drupal\mcapi_signatures\Plugin\Transition;

use Drupal\mcapi\Plugin\TransitionBase;
use Drupal\mcapi_signatures\Signatures;
use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\CurrencyInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Form\FormStateInterface;

/**
 * Sign Off transition
 *
 * @Transition(
 *   id = "sign_off"
 * )
 */
class SignOff extends Sign {

  /*
   * {@inheritdoc}
  */
  public function execute(array $values) {

    foreach ($transaction->signatures as $uid => $signed) {
      if ($signed) continue;
      $this->sign($this->transaction, User::load($uid));
    }
    $renderable = $this->baseExecute($this->transaction, $values);
    return $renderable + [
      '#markup' => t(
        '@transaction is signed off',
        ['@transaction' => $transaction->label()]
      )
    ];
  }

  /**
   * @see \Drupal\mcapi\TransitionBase::buildConfigurationForm()
   * Ensure the pending checkbox is ticked
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    unset($form['states']);
    return $form;
  }
    /*
   * {@inheritdoc}
  */
  public function accessOp(AccountInterface $account) {
    //Must be a valid transaction relative AND a named signatory.
    return parent::accessOp($account)
      && isset($this->transaction->signatures)
      && is_array($this->transaction->signatures)// signatures property is populated
      && array_key_exists(\Drupal::currentUser()->id(), $this->transaction->signatures)//the current user is a signatory
      && !$this->transaction->signatures[\Drupal::currentUser()->id()];//the currency user hasn't signed
  }

  /**
   * {@inheritdoc}
   */
  public function accessState(AccountInterface $account) {
    return $this->transaction->state->target_id == 'pending';
  }

}
