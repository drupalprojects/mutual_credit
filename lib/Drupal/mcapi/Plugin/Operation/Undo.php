<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Operations\Undo
 *
 */

namespace Drupal\mcapi\Plugin\Operation;

use Drupal\mcapi\OperationBase;
use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\CurrencyInterface;
use \Drupal\Core\Config\ConfigFactory;

/**
 * Undo operation
 *
 * @Operation(
 *   id = "undo",
 *   label = @Translation("Undo"),
 *   description = @Translation("Undo, according to global undo mode"),
 *   settings = {
 *     "weight" = "3",
 *     "sure" = "Are you sure you want to undo?"
 *   }
 * )
 */
class Undo extends OperationBase {


  //Declaration of Drupal\mcapi\Plugin\Operation\View::access_form() must be compatible with that of Drupal\mcapi\OperationInterface::access_form() in /var/www/drupal8/modules/mutual_credit/lib/Drupal/mcapi/Plugin/Operation/View.php on line 27
  public function access_form(CurrencyInterface $currency) {
    //return the access functions for each transaction state
    $element = parent::access_form($currency);
    foreach (mcapi_get_states() as $state) {
      $elements[$constantVal] = $element;
      $elements[$constantVal]['#title'] = $state->label;
      $elements[$constantVal]['#description'] = $state->description;
      $elements[$constantVal]['#default_value'] = $currency->access_undo[$state->value];
    }
  }
  /*
   *  access callback for transaction operation 'view'
  */
  public function opAccess(TransactionInterface $transaction) {
    if ($transaction->state->value == TRANSACTION_STATE_UNDONE) RETURN FALSE;
    $access_plugins = transaction_access_plugins(TRUE);
    //see the comments in OperationBase
    foreach ($transaction->worths[0] as $worth) {
      foreach (@$worth->currency->access_undo[$transaction->state->value] as $plugin) {
        if ($access_plugins[$plugin]->checkAccess($transaction)) continue 2;
      }
      return FALSE;
    }
    return TRUE;
  }

  public function execute(TransactionInterface $transaction, array $values) {
    $transaction->delete();
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

  public function settingsForm(array &$form, ConfigFactory $config) {
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
