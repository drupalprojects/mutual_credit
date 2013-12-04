<?php

/**
 * @file
 * Definition of Drupal\mcapi\TransactionViewBuilder.
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\entity\Entity\EntityDisplay;

/**
 * Render controller for nodes.
 */
class TransactionViewBuilder extends EntityViewBuilder {

  /**
   * Overrides Drupal\Core\Entity\EntityRenderController::buildContent().
   *
   * build a render array for any number of transactions
   * first arg can be one or an array of transactions, WITH CHILDREN LOADED as in transaction_load
   * $transactions an array of transactions, keyed by xid, each one having its children already loaded
   * $view mode, defaults to token with the saved transaction sentence, but an arbitrary token string can also be used
   */
  public function buildContent(array $transactions, array $displays, $view_mode = 'certificate', $langcode = NULL, $suppress_ops = FALSE) {
    //TODO Gordon for some reason the $transactions haven't got the field API fields loaded
    parent::buildContent($transactions, $displays, $view_mode, $langcode);

    foreach ($transactions as $transaction->xid => $transaction) {
      //TODO move this closer to the worths object
      foreach ($transaction->worths[0] as $currency => $worth) {
        $transaction->content['worths'][$currency] = array(
          '#prefix' => $worth->currency->prefix,
          '#suffix' => $worth->currency->suffix,
          '#markup' => $worth->quantity,
        );
      }
      $tx = array(
        '#theme' => array('mcapi_transaction'),
        '#object' => $transaction,
      );
      if ($view_mode == 'certificate') {
        //we will reveal the ajax links only on the certificate
        $tx['#links'] = $suppress_ops ? array() : transaction_get_links($transaction, TRUE, FALSE);
        $tx['#attached']['css'] = array(drupal_get_path('module', 'mcapi') .'/mcapi.css');
        $tx['certificate'] = array(
        	'#theme' => 'certificate',
        	'#object' => clone $transaction,
        );
        //because we are passing the $transaction->content down we can remove it
        $transaction->content = array();
      }
      else { //an arbitrary token string, don't forget there is a token for [transaction:links]
        $token_service = \Drupal::token();
        global $language;
        $tx['inner']['#markup'] = $token_service->replace(
          $view_mode,
          array('transaction' => $transaction),
          array('language' => $language, 'sanitize' => FALSE)
        );
      }
      $transaction->content = array_merge($transaction->content, $tx);
    }
    $type = 'transaction';//must be sent as a reference
    drupal_alter(array('transaction_view', 'entity_view'), $transaction->content, $type);
  }

}


