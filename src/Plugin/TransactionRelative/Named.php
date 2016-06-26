<?php

namespace Drupal\mcapi\Plugin\TransactionRelative;

use Drupal\mcapi\Entity\Wallet;
use Drupal\mcapi\Plugin\TransactionRelativeInterface;
use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Database\Query\Condition;

/**
 * Defines a nominated user relative to wallets in a transaction.
 *
 * @TransactionRelative(
 *   id = "named",
 *   label = @Translation("Named wallet user"),
 *   description = @Translation("A user nominated to pay in to the payer wallet or payout of the payee wallet")
 * )
 */
class Named extends PluginBase implements TransactionRelativeInterface {

  /**
   * {@inheritdoc}
   */
  public function isRelative(TransactionInterface $transaction, AccountInterface $account) {
    $targets = $this->getUsers($transaction);
    $id = $account->id();
    foreach ($targets as $target) {
      if ($id == $target['target_id']) {
        return TRUE;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function indexViewsCondition(AlterableInterface $query, Condition $or_group, $uid) {
    debug('@todo Drupal\mcapi\Plugin\TransactionRelative\Named::IndexViewsCondition');
  }

  /**
   * {@inheritdoc}
   */
  public function entityViewsCondition(AlterableInterface $query, Condition $or_group, $uid) {
    debug('@todo Drupal\mcapi\Plugin\TransactionRelative\Named::entityViewsCondition');
  }

  /**
   * {@inheritdoc}
   */
  public function getUsers(TransactionInterface $transaction) {
    $payer = $transaction->payer->entity;
    $payee = $transaction->payee->entity;
    $named = [];
    // @todo would be nice to refactor this, considering how the payers and payees fields are used elsewhere
    foreach ([$payer, $payee] as $wallet) {
      if ($wallet->payways->value == Wallet::PAYWAY_ANYONE_IN) {
        $named = array_merge($named, $wallet->payers->getValue());
      }
      elseif ($wallet->payways->value == Wallet::PAYWAY_ANYONE_OUT) {
        $named = array_merge($named, $wallet->payees->getValue());
      }
      elseif ($wallet->payways->value == Wallet::PAYWAY_ANYONE_BI) {
        // Then theres no need to name users.
      }
    }
    return $named;
  }

}
