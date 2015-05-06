<?php

/**
 * @file
 * Contains \Drupal\mcapi_limits\TransactionLimitsConstraint.
 *
 * takes the transaction and makes a projection of the sum of these plus all
 * saved transactions in a positive state against the balance limits for each
 * affected account,
 * NB. this event is only run when a transaction is inserted:
 * It only checks against transactions in a POSITIVE state
 * i.e. counted transaction.
 *
 */

namespace Drupal\mcapi_limits;

use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;

class TransactionLimitsConstraintValidator extends ConstraintValidator {

  private $replacements = [];
  private $skip = FALSE;

  /**
   * validate a transaction entity including $transaction->children[]
   * check that none of the wallets involved goes over its limits
   * Exceptions are put into $transaction->exceptions[]
   *
   * @param Transaction $transaction
   * @param Constraint $transaction
   *
   * @throws McapiException
   */
  public function validate($transaction, Constraint $constraint) {
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
        $this->replacements = ['@currency' => $currency->name];
        $config = $plugin->getConfiguration();
        if ($config['skip']['user1'] && \Drupal::currentUser()->id() == 1) {
          $this->watchdog("Skipped @currency balance limit check because you are user 1.");
        }
        elseif ($config['skip']['owner'] && \Drupal::currentUser()->id() == $currency->uid) {
          $this->watchdog("Skipped @currency balance limit check because you are the currency owner.");
        }
        elseif ($config['skip']['auto'] && $transaction->type->target_id == 'auto') {
          $this->watchdog("Skipped balance limit checks for @currency.");
        }
        elseif ($config['skip']['mass'] && $transaction->type->target_id == 'mass') {
          $this->watchdog("Skipped balance limit checks for @currency.");
        }

        if ($this->skip) return;

        $diff = array_sum($diffs);
        $projected = $wallet->getStats($curr_id)['balance'] + $diff;
        //$min and
        //$max are derived by
        extract(mcapi_limits($wallet)->limits($curr_id));
        $this->replacements = [
          '!wallet' => $wallet->label(),
          '!excess' => $currency->format($excess),
          '!limit' => $currency->format($limit),
        ];

        if ($diff > 0 && $projected > 0 && is_numeric($max) && $projected > $max) {
          $this->context->addViolation(
            //@todo ensure this is picked up by the translation system
            "The transaction would take wallet '!wallet' !excess above the maximum limit of !limit.",
            $this->replacements
          );
        }
        elseif ($diff < 0 && $projected < 0 && is_numeric($min) && $projected < $min) {
          $this->context->addViolation(
            //@todo ensure this is picked up by the translation system
            "The transaction would take wallet '!wallet' !excess below the minimum limit of !limit.",
            $this->replacements
          );
        }
      }
    }
  }

  private function watchdog($message) {
    $this->skip = TRUE;
    //@todo need some document on the $possible values of 'severity'
    //@todo inject the logger
    \Drupal::logger('mcapi_limits')->log('notice', $message, $this->replacements);

  }

}
