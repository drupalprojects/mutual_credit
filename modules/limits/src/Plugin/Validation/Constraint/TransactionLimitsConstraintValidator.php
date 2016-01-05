<?php

/**
 * @file
 * Contains \Drupal\mcapi_limits\Plugin\Validation\Constraint\TransactionLimitsConstraint.
 *
 * takes the transaction and makes a projection of the sum of these plus all
 * saved transactions in a positive state against the balance limits for each
 * affected account.
 *
 * @todo only validate transactions in a 'counted' state
 *
 */

namespace Drupal\mcapi_limits\Plugin\Validation\Constraint;

use Drupal\mcapi\Entity\Wallet;
use Drupal\mcapi\Entity\Currency;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;

class TransactionLimitsConstraintValidator extends ConstraintValidator {
  

  private $currentUser;
  private $limiter;
  private $logger;
  private $replacements = [];
  private $diffs = [];

  /**
   * @todo I don't know how to get this object to inject
   */
  function __construct() {
    $container = \Drupal::getContainer();
    $this->currentUser = $container->get('current_user');
    $this->limitManager = $container->get('plugin.manager.mcapi_limits');
    $this->limiter = \Drupal::service('mcapi_limits.wallet_limiter');
    $this->logger = $container->get('logger.factory')->get('mcapi');
  }

  /**
   * {@inheritdoc}
   */
  public function validate($transaction, Constraint $constraint) {
    //first add up all the transactions
    //to exclude the current transactions from the sum of saved transactions
    //compare the resulting balances for each wallet with its limits
    foreach ($this->differentiate($transaction) as $wid => $percurrency) {
      $wallet = Wallet::load($wid);
      foreach ($percurrency as $curr_id => $diffs) {
        //check to see if any of the skips apply.
        $currency = Currency::load($curr_id);
        $plugin = $this->limitManager->createInstanceCurrency($currency);
        if ($plugin->id === 'none') {
          continue;
        }
        //upgraded sites need to check for the presence of the skip property
        $this->replacements = ['@currency' => $currency->name];
        $config = $plugin->getConfiguration();
        if ($config['skip']['user1'] && $this->currentUser->id() == 1) {
          $this->logger->log(
            'notice', 
            'Skipped @currency balance limit check because you are user 1.', 
            $this->replacements
          );
          return;
        }
        elseif ($config['skip']['owner'] && $this->currentUser->id() == $currency->uid) {
          $this->logger->log(
            'notice', 
            'Skipped @currency balance limit check because you are the currency owner.', 
            $this->replacements
          );
          return;
        }
        elseif ($config['skip']['auto'] && $transaction->type->target_id == 'auto') {
          $this->logger->log(
            'notice', 
            'Skipped balance limit checks for @currency.', 
            $this->replacements
          );
          return;
        }
        elseif ($config['skip']['mass'] && $transaction->type->target_id == 'mass') {
          $this->logger->log(
            'notice', 
            'Skipped balance limit checks for @currency.', 
            $this->replacements
          );
          return;
        }

        $diff = array_sum($diffs);
        $projected = $wallet->getStats($curr_id)['balance'] + $diff;
        $this->limiter->setwallet($wallet);
        $max = $this->limiter->max($curr_id);
        $min = $this->limiter->min($curr_id);
        $this->replacements = [
          '%wallet' => $wallet->label()
        ];

        //@todo ideally we would ensure that we showed only the last violation message for each wallet
        //maybe by each violation being indexed with a wallet id
        if ($diff > 0 && $projected > 0 && is_numeric($max) && $projected > $max) {
          $this->replacements['%limit'] = $currency->format($max);
          $this->replacements['excess'] = $currency->format($projected - $max);
          $this->context
            ->buildViolation($constraint->overLimit, $this->replacements)
            ->atPath('payee')
            ->addViolation();
        }
        if ($diff < 0 && $projected < 0 && is_numeric($min) && $projected < $min) {
          $this->replacements['%limit'] = $currency->format($min);
          $this->replacements['excess'] = $currency->format(-$projected + $min);
          $this->context
            ->buildViolation($constraint->underLimit, $this->replacements)
            ->atPath('payer')
            ->addViolation();
        }
      }
    }
  }

  /**
   * Calculate the balance changes that this transaction proposes
   * by convention, if the transaction state < 0 it is NOT COUNTED
   * this is only used in tokens, so far, and in mcapi_limits module
   * incoming transaction can be a transaction object with children or an array
   */
  private function differentiate($transaction) {
    foreach ($transaction->flatten() as $tran) {
      foreach ($tran->worth->getValue() as $worth) {
        extract($worth); //makes variables $value and $curr_id
        //we can't prepare the array in advance with zeros so += and -= throws notices
        //instead we just build up an array and add them up later
        $this->diffs[$tran->payer->target_id][$curr_id][] = -$value;
        $this->diffs[$tran->payee->target_id][$curr_id][] = $value;
      }
    }
    return $this->diffs;
  }

}
