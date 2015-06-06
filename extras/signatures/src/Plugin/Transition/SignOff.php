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
use Drupal\Core\Config\ImmutableConfig;

/**
 * Sign Off transition
 *
 * @Transition(
 *   id = "sign_off"
 * )
 */
class SignOff extends TransitionBase {

  /*
   * {@inheritdoc}
  */
  public function execute(array $values) {
    foreach ($transaction->signatures as $uid => $signed) {
      if ($signed) continue;
      $this->sign($this->transaction, User::load($uid));
    }
    $saved = $this->transaction->save();
    return $renderable + [
      '#markup' => t(
        '@transaction is signed off',
        ['@transaction' => $transaction->label()]
      )
    ];
  }

  /*
   * {@inheritdoc}
   */
  public function accessOp(AccountInterface $account) {
    //Must be a valid transaction relative AND a named signatory.
    return parent::accessOp($account)
      && isset($this->transaction->signatures)
      && is_array($this->transaction->signatures);// signatures property is populated
  }

  /**
   * {@inheritdoc}
   */
  public function accessState(AccountInterface $account) {
    return $this->transaction->state->target_id == 'pending';
  }

  /**
   * {@inheritdoc}
   * Ensure the pending checkbox is ticked
   */
  public static function settingsFormTweak(array &$form, FormStateInterface $form_state, ImmutableConfig $config) {
    unset($form['states']);
  }


}
