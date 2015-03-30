<?php

/**
 * @file
 * Contains \Drupal\mcapi_limits\TransactionSubscriber.
 *
 * takes the transaction and makes a projection of the sum of these plus all 
 * saved transactions in a positive state against the balance limits for each 
 * affected account,
 * NB. this event is only run when a transaction is inserted:
 * It only checks against transactions in a POSITIVE state 
 * i.e. counted transactions
 * 
 */

namespace Drupal\mcapi_limits;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\mcapi\McapiEvents;

/**
 * 
 */
class TransactionSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      McapiEvents::VALIDATE => ['onValidate']
    ];
  }
  
  /**
   * validate a transaction entity including $transaction->children[]
   * check that none of the wallets involved goes over its limits
   * Exceptions are put into $transaction->exceptions[]
   *
   * @param Transaction $transaction
   * 
   * @throws McapiException
   */
  static function onValidate($event) {
    $transaction = $event->getTransaction();
    //first add up all the transactions
    //to exclude the current transactions from the sum of saved transactions
    //compare the resulting balances for each wallet with its limits
    foreach (_transactions_diff($transaction->flatten()) as $wid => $percurrency) {
      $wallet = Wallet::load($wid);
      foreach ($percurrency as $curr_id => $diffs) {
        //check to see if any of the skips apply.
        $currency = mcapi_currency_load($curr_id);
        $plugin = mcapi_limits_saved_plugin($currency);
        if ($plugin->id == 'none') continue;
        //upgraded sites need to check for the presence of the skip property
        $replacements = array('@currency' => $currency->name);
        $config = $plugin->getConfiguration();
        if ($config['skip']['user1'] && \Drupal::currentUser()->id() == 1) {
          $messages[$curr_id] = t("Skipped @currency balance limit check because you are user 1.", $replacements);
        }
        elseif ($config['skip']['owner'] && \Drupal::currentUser()->id() == $currency->uid) {
          $messages[$curr_id] = t("Skipped @currency balance limit check because you are the currency owner.", $replacements);
        }
        elseif ($config['skip']['auto'] && $transaction->type->target_id == 'auto') {
          $messages[$curr_id] = t("Skipped balance limit checks for @currency.", $replacements);
        }
        elseif ($config['skip']['mass'] && $transaction->type->target_id == 'mass') {
          $messages[$curr_id] = t("Skipped balance limit checks for @currency.", $replacements);
        }
        
        if ($messages) continue;
        
        $summary = $wallet->getStats($curr_id);
        $diff = array_sum($diffs);
        $projected = $summary['balance'] + $diff;
        $limits = mcapi_limits($wallet)->limits($curr_id);//TODO consider making this call more elegant
        
        extract ($limits);//produces $min and $max
        //we could send 'worth' values to the exception, but they are hard to work with
        //instead we'll send the currency and the quantities
        if ($diff > 0 && $projected > 0 && is_numeric($max) && $projected > $max) {
          $errors[$wid][] = array(
            'currency' => $currency,
            'limit' => $max,
            'projected' => $projected,
            'excess' => $projected - $max,
            'wallet' => $wallet
          );
        }
        elseif ($diff < 0 && $projected < 0 && is_numeric($min) && $projected < $min) {
          $errors[$wid][] = array(
            'currency' => $currency,
            'limit' => $min,
            'projected' => $projected,
            'excess' => $min - $projected,
            'wallet' => $wallet
          );
        }
      }
    }
    //all balance errors are on the main transaction and can stop.
    if (count($errors)) {
      //reformat the errors
      foreach ($errors as $wid => $info) {
        foreach ($info as $delta => $vars) {
          //TODO this shouldn't be an exception. 
          //SHould pass to allow the worth widget to handle it
          $transaction['errors'] = new McapiLimitsException(
            $vars['currency'], 
            $vars['limit'], 
            $vars['projected'], 
            $vars['excess'], 
            $vars['wallet']
          );
        }
      }
    }
    if (count($messages) && \Drupal::currentUser()->hasPermission('configure mcapi')) {
      foreach ($messages as $curr_id => $message) {
        drupal_set_message($message, 'warning', FALSE);
      }
    }
  }
  
}
