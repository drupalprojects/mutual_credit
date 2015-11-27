<?php

/**
 * @file
 *  Contains Drupal\mcapi_signatures\Plugin\TransactionRelative\Signatory
 */

namespace Drupal\mcapi_signatures\Plugin\TransactionRelative;

use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\mcapi\Plugin\TransactionRelativeInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\PluginBase;

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
  function condition(QueryInterface $query) {

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
