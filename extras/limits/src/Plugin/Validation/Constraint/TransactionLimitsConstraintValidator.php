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
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TransactionLimitsConstraintValidator extends ConstraintValidator {

  use DependencySerializationTrait;

  private $replacements = [];
  private $currentUser;
  private $limiter;
  private $logger;

  function __construct($limitManager, $limiter, $logger) {
    $this->currentUser = $limitManager;
    $this->limitManager = $limitManager;
    $this->limiter = $limiter;
    $this->logger = $logger->get('mcapi_limits');
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('plugin.manager.mcapi_limits'),
      $container->get('mcapi_limits.wallet_limiter'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($transaction, Constraint $constraint) {
    //first add up all the transactions
    //to exclude the current transactions from the sum of saved transactions
    //compare the resulting balances for each wallet with its limits
    foreach ($this->diff($transaction) as $wid => $percurrency) {
      $wallet = Wallet::load($wid);
      foreach ($percurrency as $curr_id => $diffs) {
        //check to see if any of the skips apply.
        $currency = mcapi_currency_load($curr_id);
        $plugin = $this->limitManager->createInstanceCurrency($currency);
        if ($plugin->id === 'none') {
          continue;
        }
        //upgraded sites need to check for the presence of the skip property
        $this->replacements = ['@currency' => $currency->name];
        $config = $plugin->getConfiguration();
        if ($config['skip']['user1'] && $this->currentUser->id() == 1) {
          $this->watchdog("Skipped @currency balance limit check because you are user 1.");
        }
        elseif ($config['skip']['owner'] && $this->currentUser->id() == $currency->uid) {
          $this->watchdog("Skipped @currency balance limit check because you are the currency owner.");
        }
        elseif ($config['skip']['auto'] && $transaction->type->target_id == 'auto') {
          $this->watchdog("Skipped balance limit checks for @currency.");
        }
        elseif ($config['skip']['mass'] && $transaction->type->target_id == 'mass') {
          $this->watchdog("Skipped balance limit checks for @currency.");
        }

        if ($this->skip) {
          return;
        }

        $diff = array_sum($diffs);
        $projected = $wallet->getStats($curr_id)['balance'] + $diff;
        //$min and
        //$max are derived by
        //@todo inject the wallet limiter?
        $this->limiter->setwallet($wallet);
        $max = $this->limiter->max($curr_id);
        $min = $this->limiter->min($curr_id);
        $this->replacements = [
          '!wallet' => $wallet->label()
        ];

        if ($diff > 0 && $projected > 0 && is_numeric($max) && $projected > $max) {
          $this->replacements['!limit'] = $currency->format($max);
          $this->replacements['excess'] = $currency->format($projected - $max);
          $this->context
            ->buildViolation($constraint->overLimit, $this->replacements)
            ->atPath('payee')
            ->addViolation();
        }
        if ($diff < 0 && $projected < 0 && is_numeric($min) && $projected < $min) {
          $this->replacements['!limit'] = $currency->format($min);
          $this->replacements['excess'] = $currency->format(-$projected + $min);
          $this->context
            ->buildViolation($constraint->underLimit, $this->replacements)
            ->atPath('payer')
            ->addViolation();
        }
      }
    }
  }

  private function watchdog($message) {
    $this->skip = TRUE;
    $this->logger->log('notice', $message, $this->replacements);
  }

  /**
   * Calculate the balance changes that this transaction proposes
   * by convention, if the transaction state < 0 it is NOT COUNTED
   * this is only used in tokens, so far, and in mcapi_limits module
   * incoming transaction can be a transaction object with children or an array
   */
  private function diff($transaction) {
    foreach ($transaction->flatten() as $tran) {
      foreach ($tran->worth->getValue() as $worth) {
        extract($worth); //makes variables $value and $curr_id
        //we can't prepare the array in advance with zeros so += and -= throws notices
        //instead we just build up an array and add them up later
        $diff[$tran->payer->target_id][$curr_id][] = -$value;
        $diff[$tran->payee->target_id][$curr_id][] = $value;
      }
    }
    return $diff;
  }

}
