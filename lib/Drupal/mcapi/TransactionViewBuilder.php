<?php

/**
 * @file
 * Definition of Drupal\node\NodeViewBuilder.
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

    parent::buildContent($transactions, $displays, $view_mode, $langcode);
    //special case for the 'are you sure' form where the transaction was test written and deleted
    //TODO probably not needed in D8?
    if (!property_exists(current($transactions), 'serial') || empty($transaction->serial->value)) {
      foreach ($transactions as $transaction) {
        $variables['classes_array'][] = 'preview';
        //remove all fieldAPI fields because the entity isn't in the database yet and doesn't have an entity_id
        foreach (field_info_instances('transaction', 'transaction') as $fieldname => $instance) {
          unset($transaction->{$fieldname});
        }
      }
    }
    else {
      field_attach_prepare_view('transaction', $transactions, 'certificate');
    }

    foreach ($transactions as $transaction) {
      //temporary for the worth field
      foreach ($transaction->worths[0] as $currency => $worth) {
        $transaction->content['worths'][$currency] = array(
          '#prefix' => $worth->currency->prefix,
          '#suffix' => $worth->currency->suffix,
          '#markup' => $worth->quantity,
        );
      }

      $tx = array(
        '#theme_wrappers' => array('mcapi_transaction'),
        '#class' => array(
           'transaction',
           $view_mode == 'certificate' ? 'certificate' : 'sentence',
           'state-'.$transaction->state->value,
           $transaction->type->value
        ),
        '#object' => $transaction,
      );
      switch ($view_mode) {
        case 'certificate':
          $tx['#theme'] = 'certificate';
          //we will reveal the ajax links only on the certificate
          $tx['#links'] = $suppress_ops ? array() : transaction_get_links($transaction, TRUE, FALSE);
          $tx['#attached']['css'] = array(drupal_get_path('module', 'mcapi') .'/mcapi.css');
          debug($tx['#attached']);
          break;
        default: //an arbitrary token string, don't forget there is a token for [transaction:links]
          $token_service = \Drupal::token();
          global $language;
          $tx['#markup'] = $token_service->replace(
            $view_mode,
            array('transaction' => $transaction),
            array('language' => $language, 'sanitize' => FALSE)
          );
          break;
      }
      $transaction->content = $tx;
    }
    $type = 'transaction';//must be sent as a reference
    drupal_alter(array('transaction_view', 'entity_view'), $transaction->content, $type);
  }

}
