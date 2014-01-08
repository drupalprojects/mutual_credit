<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Operations\SignOff
 *
 */

namespace Drupal\mcapi_signatures\Plugin\Operation;

use Drupal\mcapi\OperationBase;
use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\CurrencyInterface;
use \Drupal\Core\Config\ConfigFactory;

/**
 * Sign Off operation
 *
 * @Operation(
 *   id = "sign_off",
 *   label = @Translation("Sign off"),
 *   description = @Translation("Sign a pending transaction on behalf of all pending signatories"),
 *   settings = {
 *     "weight" = "2",
 *     "sure" = "Are you sure you want to finalise this transaction?"
 *   }
 * )
 */
class SignOff extends OperationBase {

  /*
   * {@inheritdoc}
  */
  public function execute(TransactionInterface $transaction, array $values) {
    //TODO make the temp notifications work
    /*
    $mail_settings = $this->config->get('special');
    if ($mail_settings['send'] && $mail_settings['subject'] && $mail_settings['body']) {
      //here we are just sending one mail, in one language
      global $language;
      $to = implode(user_load($transaction->payer)->mail, user_load($transaction->payee)->mail);
      $params['transaction'] = $transaction;
      $params['config'] = $this->configFactory->get('mcapi.operation.sign');
      drupal_mail('mcapi', 'operation', $to, $language->language, $params);
    }*/
    foreach ($transaction->signatures as $uid => $signed) {
      if ($signed) continue;
      transaction_sign($transaction, user_load($uid));
    }
    return array(
      '#markup' => t(
        '@transaction is signed off',
        array('@transaction' => $transaction->label())
      )
    );
  }

  /*
   * {@inheritdoc}
   */
  public function opAccess(TransactionInterface $transaction) {
    if ($transaction->state->value == TRANSACTION_STATE_PENDING) {
      return parent::opAccess($transaction);
    }
  }

  /*
   * {@inheritdoc}
  */
  public function settingsForm(array &$form, ConfigFactory $config) {
    //TODO mail notifications should probably be abstracted to the operation base
    $conf = $config->get('special');
    $form['special'] = array(
      '#type' => 'fieldset',
      '#title' => t('Mail the transactees'),
      '#description' => t('TODO: This should be replaced by rules.'),
      '#weight' => 0
    );
    $form['special']['send'] = array(
       '#title' => t('Notify both transactees'),
      '#type' => 'checkbox',
       '#default_value' => $conf['send'],
       '#weight' =>  0
    );
    $form['special']['subject'] = array(
       '#title' => t('Mail subject'),
       '#description' => '',
      '#type' => 'textfield',
       '#default_value' => $conf['subject'],
       '#weight' =>  1,
      '#states' => array(
        'visible' => array(
          ':input[name="special[send]"]' => array('checked' => TRUE)
        )
      )
    );
    $form['special']['body'] = array(
       '#title' => t('Mail body'),
       '#description' => '',
       '#type' => 'textarea',
       '#default_value' => $conf['body'],
       '#weight' => 2,
      '#states' => array(
        'visible' => array(
          ':input[name="special[send]"]' => array('checked' => TRUE)
        )
      )
    );
    $form['special']['cc'] = array(
       '#title' => t('Carbon copy to'),
       '#description' => 'A valid email address',
       '#type' => 'email',
       '#default_value' => $conf['cc'],
       '#weight' => 3,
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
