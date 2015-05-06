<?php

/**
 * @file
 * Contains Drupal\mcapi_signatures\Plugin\Transition\Sign
 * @todo reduce duplication with the SignOff Plugin
 *
 */

namespace Drupal\mcapi_signatures\Plugin\Transition;

use Drupal\mcapi\Plugin\TransitionBase;
use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\CurrencyInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormStateInterface;

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
  public function execute(TransactionInterface $transaction, array $values) {
    module_load_include('inc', 'mcapi_signatures');
    transaction_sign($transaction, \Drupal::currentUser());

    if ($transaction->state->target_id == TRANSACTION_STATE_FINISHED) {
      $message = t('@transaction is signed off', ['@transaction' => $transaction->label()]);
    }
    else{
      $vals = array_count_values($transaction->signatures);
      $num_unsigned = $vals[0];
      $message = \Drupal::Translation()->formatPlural(
        $num_unsigned,
        '1 signature remaining',
        '@count signatures remaining'
      );
    }
    return ['#markup' => $message];
  }

  /**
   * @see \Drupal\mcapi\TransitionBase::buildConfigurationForm()
   * Ensure the pending checkbox is ticked
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['states'][TRANSACTION_STATE_PENDING]['#value'] = TRUE;
    $form['states'][TRANSACTION_STATE_PENDING]['#disabled'] = TRUE;
    $form['states'][TRANSACTION_STATE_FINISHED]['#value'] = FALSE;
    $form['states'][TRANSACTION_STATE_FINISHED]['#disabled'] = TRUE;
    return $form;
  }

  /*
   * {@inheritdoc}
  */
  public function accessOp(TransactionInterface $transaction, AccountInterface $account) {
    //Must be a valid transaction relative AND a named signatory.
    return parent::accessOp()
      && isset($transaction->signatures)
      && is_array($transaction->signatures)// signatures property is populated
      && array_key_exists(\Drupal::currentUser()->id(), $transaction->signatures)//the current user is a signatory
      && !$transaction->signatures[\Drupal::currentUser()->id()];//the currency user hasn't signed
  }

  /**
   * The default plugin access allows selection of transaction relatives.
   *
   * @param array $element
   */
  protected function accessSettingsForm(&$element) {
    $element['access'] = ['#markup' => $this->t('Only named signatories can sign.')];
  }

}
