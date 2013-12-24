<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Operations\View
 *  View is a special operation because it does nothing except link
 */

namespace Drupal\mcapi\Plugin\Operation;

use Drupal\mcapi\OperationBase;
use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\CurrencyInterface;
use \Drupal\Core\Config\ConfigFactory;

/**
 * Links to the transaction certificate
 *
 * @Operation(
 *   id = "view",
 *   label = @Translation("View"),
 *   description = @Translation("Visit the transaction's page"),
 *   settings = {
 *     "weight" = "1",
 *     "sure" = ""
 *   }
 * )
 */
class View extends OperationBase {//does it go without saying that this implements OperationInterface

  //Declaration of Drupal\mcapi\Plugin\Operation\View::access_form() must be compatible with that of Drupal\mcapi\OperationInterface::access_form() in /var/www/drupal8/modules/mutual_credit/lib/Drupal/mcapi/Plugin/Operation/View.php on line 27
  public function access_form(CurrencyInterface $currency) {
    //return the access functions for each transaction state
    $element = parent::access_form($currency);
    foreach (mcapi_get_states() as $constantVal => $state) {
      $elements[$constantVal] = $element;
      $elements[$constantVal]['#title'] = $state['name'];
      $elements[$constantVal]['#description'] = $state['description'];
      $elements[$constantVal]['#default_value'] = $currency->access_view[$constantVal];
    }
  }

  /*
   *  access callback for transaction operation 'view'
  */
  public function opAccess(TransactionInterface $transaction) {
    $access_plugins = transaction_access_plugins(TRUE);
    //see the comments in OperationBase
    foreach ($transaction->worths[0] as $worth) {
      foreach ($worth->currency->access_view[$transaction->state->value] as $plugin) {
        if ($access_plugins[$plugin]->checkAccess($transaction)) continue 2;
      }
      return FALSE;
    }
    return TRUE;
  }

  /*
   * this is not used in View which is a special case.
   */
  public function execute(TransactionInterface $transaction, array $values) {

  }

  public function settingsForm(array &$form, ConfigFactory $config) {
    return array();
  }
}
