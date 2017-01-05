<?php

namespace Drupal\mcapi\Plugin\TransactionRelative;

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
 *   id = "burser",
 *   label = @Translation("Burser"),
 *   description = @Translation("A user nominated to pay in to the payer wallet or payout of the payee wallet")
 * )
 */
class Burser extends PluginBase implements TransactionRelativeInterface {

  /**
   * {@inheritdoc}
   */
  public function isRelative(TransactionInterface $transaction, AccountInterface $account) {
    $targets = $this->getUsers($transaction);
    return in_array($account->id(), $targets);
  }

  /**
   * {@inheritdoc}
   */
  public function entityViewsCondition(AlterableInterface $query, Condition $or_group, $uid) {
    $query->join('mcapi_wallet', 'burser_wallet_payer', "burser_wallet_payer.wid = base_table.payer AND burser_wallet_payer.holder_entity_type = 'user'");
    $query->join('mcapi_wallet', 'burser_wallet_payee', "burser_wallet_payee.wid = base_table.payee AND burser_wallet_payee.holder_entity_type = 'user'");
    $query->leftjoin('mcapi_wallet__bursers', 'bursers_payer', "bursers_payer.entity_id = burser_wallet_payer.wid");
    $query->leftjoin('mcapi_wallet__bursers', 'bursers_payee', "bursers_payee.entity_id = burser_wallet_payee.wid");
    $b_or_group = $query->orConditionGroup();
    $b_or_group->condition('bursers_payer.bursers_target_id', $uid)->condition('bursers_payee.bursers_target_id', $uid);
    $or_group->condition($b_or_group);
  }

  /**
   * Get the users who have bursor power over one of the wallets in the transaction
   *
   * @param TransactionInterface $transaction
   * @return int[]
   *   The ids of the users.
   */
  public function getUsers(TransactionInterface $transaction) {
    $payer_wallet = $transaction->payer->entity;
    $payee_wallet = $transaction->payee->entity;
    $bursers = [];
    // @todo would be nice to refactor this, considering how the payers and payees fields are used elsewhere
    foreach ([$payer_wallet, $payee_wallet] as $wallet) {
      foreach($wallet->bursers->referencedEntities() as $user) {
        $bursers[] = $user->id();
      }
    }
    return $bursers;
  }

}
