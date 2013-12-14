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
   * $view mode, defaults to certificate with the saved transaction sentence, but an arbitrary token string can also be used
   */
  public function buildContent(array $transactions, array $displays, $view_mode = 'certificate', $langcode = NULL) {
    parent::buildContent($transactions, $displays, $view_mode, $langcode);
    module_load_include('inc', 'mcapi');
    foreach ($transactions as $transaction->xid => $transaction) {
      $renderable = array(
          '#theme_wrappers' => array('mcapi_transaction'),
          '#transaction' => $transaction,
          'links' => transaction_get_links($transaction, 'ajax', FALSE),
      );
      if ($view_mode == 'certificate') {
        $renderable['#theme'] = 'certificate';
        //css helps rendering the default certificate
        $renderable['#attached'] = array('css' => array(drupal_get_path('module', 'mcapi') .'/mcapi.css'));
      }
      else {//assume it is twig
        $renderable['customtwig'] = array(
          '#markup' => mcapi_render_twig_transaction($view_mode, $transaction)
        );
      }
      /*
      else {//tokens are deprecated for now
        $token_service = \Drupal::token();
        global $language;
        $tx['inner']['#markup'] = $token_service->replace(
          $view_mode,
          array('transaction' => $transaction),
          array('language' => $language, 'sanitize' => FALSE)
        );
      }
      */
      $transaction->content = $renderable;
    }
  }

}


