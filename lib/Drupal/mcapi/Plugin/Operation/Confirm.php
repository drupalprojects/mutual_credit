<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Operations\Confirm
 */

namespace Drupal\mcapi\Plugin\Operation;

use Drupal\mcapi\OperationBase;
use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\CurrencyInterface;
use \Drupal\Core\Config\ConfigFactory;

/**
 * Confirm operation
 *
 * @Operation(
 *   id = "confirm",
 *   label = @Translation("Confirm"),
 *   description = @Translation("Confirm a new transaction"),
 *   settings = {
 *     "weight" = "0",
 *     "sure" = "Are you sure?"
 *   }
 * )
 */
class Confirm extends OperationBase {

  /*
   * {@inheritdoc}
   */
  public function access_form(CurrencyInterface $currency) {
    return array();
  }

  /*
   * {@inheritdoc}
   */
  public function opAccess(TransactionInterface $transaction) {
    return empty($transaction->serial->value);
  }

  /*
   * Save the transaction
  *
  * @param TransactionInterface $transaction
  *   A transaction entity object
  * @param array $values
  *   the contents of $form_state['values']
  *
  * @return string
  *   an html snippet for the new page, or which in ajax mode replaces the form
  */
  public function execute(TransactionInterface $transaction, array $values) {

/*
    $mail_settings = $this->config->get('special');
    //TODO make the temp notifications work
    if ($mail_settings['send'] && $mail_settings['subject'] && $mail_settings['body']) {
      //here we are just sending one mail, in one language
      global $language;
      $to = implode(user_load($transaction->payer)->mail, user_load($transaction->payee)->mail);
      $params['transaction'] = $transaction;
      $params['config'] = $this->configFactory->get('mcapi.operation.undo');
      drupal_mail('mcapi', 'operation', $to, $language->language, $params);
    }
*/
    $message = t('The transaction is undone.') .' ';
    return array('#markup' => $message);
  }

  /*
   * {@inheritdoc}
  */
  public function settingsForm(array &$form, ConfigFactory $config) {
    parent::settingsForm($form, $config);

    unset($form['sure']['button'], $form['sure']['cancel_button'], $form['notify'], $form['op_title']);
    print_r(element_children($form));
    return $form;
  }

}
