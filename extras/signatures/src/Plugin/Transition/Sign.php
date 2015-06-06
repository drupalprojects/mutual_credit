<?php

/**
 * @file
 * Contains Drupal\mcapi_signatures\Plugin\Transition\Sign
 * @todo reduce duplication with the SignOff Plugin
 *
 */

namespace Drupal\mcapi_signatures\Plugin\Transition;

use Drupal\mcapi_signatures\Signatures;
use Drupal\mcapi\Plugin\TransitionBase;
use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\CurrencyInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ImmutableConfig;

/**
 * Sign transition
 *
 * @Transition(
 *   id = "sign"
 * )
 */
class Sign extends TransitionBase {

  /*
   * {@inheritdoc}
  */
  public function execute(array $values) {
    Signatures::sign($this->transaction, \Drupal::currentUser());
    $saved = $this->transaction->save();

    if ($this->transaction->state->target_id == TRANSACTION_STATE_FINISHED) {
      $message = t('@transaction is signed off', ['@transaction' => $this->transaction->label()]);
    }
    else{
      $vals = array_count_values($this->transaction->signatures);
      $num_unsigned = $vals[0];
      $message = \Drupal::Translation()->formatPlural(
        $num_unsigned,
        '1 signature remaining',
        '@count signatures remaining'
      );
    }
    return $renderable + ['#markup' => $message];
  }

  /**
   * Ensure the pending checkbox is ticked
   */
  public static function settingsFormTweak(array &$form, FormStateInterface $form_state, ImmutableConfig $config) {
    unset($form['states']);
  }

  /**
   * {@inheritdoc}
   */
  public function accessState(AccountInterface $account) {
    return $this->transaction->state->target_id == 'pending';
  }

  /*
   * {@inheritdoc}
  */
  public function accessOp(AccountInterface $account) {
    //Must be a valid transaction relative AND a named signatory.
    return parent::accessOp($account)
      && isset($this->transaction->signatures)
      && is_array($this->transaction->signatures)// signatures property is populated
      && array_key_exists(\Drupal::currentUser()->id(), $transaction->signatures)//the current user is a signatory
      && !$this->transaction->signatures[\Drupal::currentUser()->id()];//the currency user hasn't signed
  }

  /**
   * The default plugin access allows selection of transaction relatives.
   *
   * @param array $element
   */
  static function accessSettingsElement(&$element, $default) {
    $element['access'] = ['#markup' => t('Only named signatories can sign.')];
  }

    /**
   * sign a transaction
   * change the state if no more signatures are left
   * would be nice if this was in a decorator class so $transaction->sign($account) is possible
   * @param TransactionInterface $transaction
   * @param AccountInterface $account
   */
  final function sign(AccountInterface $account) {
    if (array_key_exists($account->id(), $this->transaction->signatures)) {
      $this->transaction->signatures[$account->id()] = REQUEST_TIME;
      //set the state to finished if there are no outstanding signatures
      if (array_search(0, $this->transaction->signatures) === FALSE) {
        $this->transaction->set('state', TRANSACTION_STATE_FINISHED);
      }
    }
  }

}
