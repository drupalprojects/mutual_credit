<?php

/**
 * @file
 *  Contains Drupal\mcapi_signatures\Plugin\TransactionRelative\Signatory
 */

namespace Drupal\mcapi_signatures\Plugin\TransactionRelative;

use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\mcapi\Plugin\TransactionRelativeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Database\Query\AlterableInterface;

/**
 * Defines a payee relative to a Transaction entity.
 *
 * @TransactionRelative(
 *   id = "signatory",
 *   label = @Translation("Any signatory"),
 *   description = @Translation("Users whose signature was required")
 * )
 */
class Signatory extends PluginBase implements TransactionRelativeInterface {

  /**
   * {@inheritdoc}
   */
  function isRelative(TransactionInterface $transaction, AccountInterface $account) {
    return array_key_exists($account->id(), $transaction->signatories);
  }

  /**
   * {@inheritdoc}
   */
  public function indexViewsCondition(AlterableInterface $query, $or_group, $uid) {

  }
  /**
   * {@inheritdoc}
   */
  public function entityViewsCondition(AlterableInterface $query, $or_group, $uid) {
    $query->join('mcapi_wallet', 'signature_wallets', 'mcapi_transaction.payer = signature_wallets.wid OR mcapi_transaction.payee = signature_wallets.wid');
    $query->join('users', 'signatory_users', "users.uid = signature_wallets.wallet_holder_id. AND signature_wallets.wallet_holder_type = 'user'");
    $query->join('mcapi_signatures', 'signatories', 'signatories.uid = owner.uid');
    $or_group->condition('signatories.uid', $uid);
  }

  /**
   * {@inheritdoc}
   */
  function getUsers(TransactionInterface $transaction) {
    return $this->database->select('mcapi_signatures', 's')->fields('s', ['uid'])
      ->condition('serial', $transaction->serial->value)
      ->execute()->fetchCol();
  }

}
