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
use Symfony\Component\DependencyInjection\ContainerInterface;

class TransactionLimitsConstraintValidator extends ConstraintValidator implements \Drupal\Core\DependencyInjection\ContainerInjectionInterface{

  private $currentUser;
  private $limiter;
  private $logger;
  private $mailManager;
  private $eventDispatcher;
  private $replacements = [];
  private $lookup = [];

  /**
   * @todo I don't know how to get this object to inject
   */
  function __construct($current_user, $limits_manager, $wallet_limiter, $logger, $mail_manager, $event_dispatcher) {
    $this->currentUser = $current_user;
    $this->limitManager = $limits_manager;
    $this->limiter = $wallet_limiter;
    $this->logger = $logger;
    $this->mailManager = $mail_manager;
    $this->eventDispatcher = $event_dispatcher;
  }

  static function create(ContainerInterface $container) {
    return new static (
      $container->get('current_user'),
      $container->get('plugin.manager.mcapi_limits'),
      $container->get('mcapi_limits.wallet_limiter'),
      $container->get('logger.factory')->get('mcapi'),
      $container->get('plugin.manager.mail'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($transaction, Constraint $constraint) {
    $transaction->mailWarning = [];
    //first add up all the transactions
    //to exclude the current transactions from the sum of saved transactions
    //compare the resulting balances for each wallet with its limits
    foreach ($this->differentiate($transaction) as $wid => $percurrency) {
      $wallet = Wallet::load($wid);
      foreach ($percurrency as $delta => $worth) {
        $curr_id = $worth['curr_id'];
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
        $diff = $worth['value'];
        $projected = $wallet->getStats($curr_id)['balance'] + $diff;
        $this->limiter->setwallet($wallet);
        $max = $this->limiter->max($curr_id);
        $min = $this->limiter->min($curr_id);
        $this->replacements = [
          '%wallet' => $wallet->label()
        ];
        $prevent = $currency->getThirdPartySetting('mcapi_limits', 'prevent');
        if ($diff > 0 && $projected > 0 && is_numeric($max) && $projected > $max) {
          $this->replacements['%limit'] = strip_tags($currency->format($max));
          $this->replacements['%excess'] =  strip_tags($currency->format($projected - $max));
          if ($prevent) {
            $this->context
              ->buildViolation($constraint->overLimitBlock, $this->replacements)
              ->atPath('worth.'.$delta)
              ->addViolation();
            $event = new \Drupal\mcapi_limits\Event\TransactionPreventedEvent(
              $transaction,
              [\Drupal::translation()->translate($constraint->overLimitBlock, $this->replacements)]
            );
            $this->eventDispatcher->dispatch(\Drupal\mcapi_limits\McapiLimitsEvents::PREVENTED, $event);
            //@todo remove this warning mail
            $this->warningMail(
              $currency,
              $transaction->payee->entity,
              $transaction->payer->entity,
              \Drupal::translation()->translate($constraint->overLimitBlock, $this->replacements)
            );
          }
          else {
            $message =  \Drupal::translation()->translate($constraint->overLimitWarning, $this->replacements);
            //this is used by Drupal\rules\Plugin\Condition\TransactionTransgresses
            $transaction->mailLimitsWarning[$wid][$currency->id()] = $message;
          }
        }
        if ($diff < 0 && $projected < 0 && is_numeric($min) && $projected < $min) {
          $this->replacements['%limit'] = strip_tags($currency->format($min));
          $this->replacements['%excess'] = strip_tags($currency->format(-$projected + $min));
          if ($prevent) {
            $this->context
              ->buildViolation($constraint->underLimitBlock, $this->replacements)
              ->atPath('worth.'.$delta)
              ->addViolation();

            $event = new \Drupal\mcapi_limits\Event\TransactionPreventedEvent(
              $transaction,
              [\Drupal::translation()->translate($constraint->overLimitBlock, $this->replacements)]
            );
            $this->eventDispatcher->dispatch(\Drupal\mcapi_limits\McapiLimitsEvents::PREVENTED, $event);
            //@todo remove this warning mail
            $this->warningMail(
              $currency,
              $transaction->payer->entity,
              $transaction->payee->entity,
              \Drupal::translation()->translate($constraint->overLimitBlock, $this->replacements)
            );
          }
          else {
            $message =  \Drupal::translation()->translate($constraint->overLimitWarning, $this->replacements);
            //this is used by Drupal\rules\Plugin\Condition\TransactionTransgresses
            $transaction->mailLimitsWarning[$wid][$currency->id()] = $message;
          }
        }
      }
    }
  }

  /**
   * warn the owner of the exceeding wallet, if its not the current user.
   * @note this could be called more than once at the moment
   * @todo make this work on an event with rules
   */
  private function warningMail($currency, Wallet $exceeded_wallet, Wallet $partner_wallet, $warning_message) {
    static $sentTo = [];
    $owner = $exceeded_wallet->getOwner();
    if (in_array($owner->id(), $sentTo)) {
      //ensure that in a multiple currency transaction, only one mail is sent
      return;
    }
    $body = $currency->getThirdPartySetting('mcapi_limits', 'warning_mail')['body'];
    if (strlen($body)) {
      $this->mailManager->mail(
        'mcapi_limits',
        'warning',
        $owner->getEmail(),
        $owner->getPreferredLangcode(),
        [
          'subject' => $currency->getThirdPartySetting('mcapi_limits', 'warning_mail')['subject'],
          'body' => str_replace('[message]', $warning_message, $body),
          'exceeded_wallet' => $exceeded_wallet,
          'partner_wallet' => $partner_wallet,
          'message' => $warning_message,
        ]
      );
      $sent[] = $owner->id();
    }
  }


  /**
   * Calculate the balance changes that this transaction proposes
   * by convention, if the transaction state < 0 it is NOT COUNTED
   * this is only used in tokens, so far, and in mcapi_limits module
   * incoming transaction can be a transaction object with children or an array
   */
  private function differentiate($transaction) {
    $this->setLookup($transaction);
    $diffs = $diff_worths = [];
    foreach ($transaction->flatten() as $tran) {
      foreach ($tran->worth->getValue() as $delta => $worth) {
        extract($worth); //makes variables $value and $curr_id
        //ugly but it initiates a value of 0 for every currency we haven't seen yet
        if (!isset($diffs[$tran->payer->target_id][$curr_id])) {
          $diffs[$tran->payer->target_id][$curr_id] = 0;
        }
        if (!isset($diffs[$tran->payee->target_id][$curr_id])) {
          $diffs[$tran->payee->target_id][$curr_id] = 0;
        }
        //we can't prepare the array in advance with zeros so += and -= throws notices
        //instead we just build up an array and add them up later
        $diffs[$tran->payer->target_id][$curr_id] -= $value;
        $diffs[$tran->payee->target_id][$curr_id] += $value;
      }
    }
    //having got the totals, lets put them in a more recoginsable format
    //respecting the worth items deltas, with anything else tacked on the end
    foreach ($diffs as $wid => $currs) {
      foreach ($currs as $curr_id => $diff) {
        $diff_worths[$wid][$this->lookup($curr_id)] = [
          'curr_id' => $curr_id,
          'value' => $diff
        ];
      }
    }
    return $diff_worths;
  }

  private function lookup($curr_id) {
    if (!in_array($curr_id, $this->lookup)) {
      $this->lookup[] = $curr_id;
    }
    return array_search($curr_id, $this->lookup);
  }

  private function setLookup($transaction) {
    foreach ($transaction->worth->getValue() as $delta => $worth) {
      $this->lookup[$delta] = $worth['curr_id'];
    }
  }

}
