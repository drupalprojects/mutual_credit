<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Operations\Sign
 *
 */

namespace Drupal\mcapi_signatures\Plugin\Operation;

use Drupal\mcapi\OperationBase;
use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\CurrencyInterface;
use \Drupal\Core\Config\ConfigFactory;

/**
 * Sign operation
 *
 * @Operation(
 *   id = "sign",
 *   label = @Translation("Sign"),
 *   description = @Translation("Sign a pending transaction"),
 *   settings = {
 *     "weight" = "2",
 *     "sure" = "Are you sure you want to sign?"
 *   }
 * )
 */
class Sign extends OperationBase {


  public function execute(TransactionInterface $transaction, array $values) {
    $mail_settings = $this->config->get('special');

    //TODO make the temp notifications work
    if ($mail_settings['send'] && $mail_settings['subject'] && $mail_settings['body']) {
      //here we are just sending one mail, in one language
      global $language;
      $to = implode(user_load($transaction->payer)->mail, user_load($transaction->payee)->mail);
      $params['transaction'] = $transaction;
      $params['config'] = $this->configFactory->get('mcapi.operation.sign');
      drupal_mail('mcapi', 'operation', $to, $language->language, $params);
    }
    transaction_sign($serial, $uid);

    if ($transaction->state->value == TRANSACTION_STATE_FINISHED) {
      $message = t('@transaction is signed off', array('@transaction' => $transaction->label()));
    }
    else{
      $message = \Drupal::TranslationManager()->format_plural($num, '1 signature remaining', '@count signatures remaining');
    }

    return array('#markup' => $message);
  }


  //no configuration for this - only the designated signatories can sign
  //however see the other operation 'sign_off'
  public function access_form(CurrencyInterface $currency) {
    return array();
  }


  public function opAccess(TransactionInterface $transaction) {
    if ($transaction->state->value == TRANSACTION_STATE_PENDING
      && array_key_exists('signatures', $transaction)
      && is_array($transaction->signatures)
      && array_key_exists(\Drupal::currentUser()->id(), $transaction->signatures)
      && $transaction->signatures[\Drupal::currentUser()->id()] == ''
    ) return TRUE;
  }

  public function settingsForm(array &$form, ConfigFactory $config) {
    //TODO mail notifications should probably be abstracted to the operation base
    $conf = $config->get('special');

    $form['special']['countersignatories'] = array(
      '#title' => t('Counter-signatories'),
      '#description' => 'Other users required to sign the transaction',
      '#type' => 'user_chooser_few',
      '#callback' => 'user_chooser_segment_perms',
      '#args' => array('transact'),
      '#default_value' => $conf['countersignatories'],
      '#weight' => 3,
      '#multiple' => TRUE,
      '#required' => FALSE,
      '#states' => array(
        'visible' => array(
          ':input[name="special[send]"]' => array('checked' => TRUE)
        )
      )
    );
    parent::settingsForm($form, $config);
    return $form;
  }
}
