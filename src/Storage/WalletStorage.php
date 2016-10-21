<?php

namespace Drupal\mcapi\Storage;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\mcapi\Entity\Wallet;

/**
 * Storage controller for wallet entity.
 */
class WalletStorage extends SqlContentEntityStorage implements WalletStorageInterface {

  /**
   * {@inheritdoc}
   */
  protected function doCreate(array $values) {
    $entity = parent::doCreate($values);
    $entity->setHolder($values['holder']);
    return $entity;
  }

  /**
   * {@inheritdoc}
   *
   * Similar to an entityQuery, but not!
   *
   * @todo this is called more than once per user per transaction form. So
   * either cache the results, with right tags, or at least save the results
   * per $uid and operation.
   */
  public function whichWalletsQuery($operation, $uid, $match = '') {
    $query = $this->database->select('mcapi_wallet', 'base_table')
      ->fields('base_table', ['wid'])
      ->condition('orphaned', 0);
    if ($match) {
      $query->condition('base_table.name', '%' . $this->database->escapeLike($match) . '%', 'LIKE');
    }
    // Include users who have been nominated to pay in or out of wallets.
    $or = $query->orConditionGroup();

    if ($operation) {
      $users = $query->orConditionGroup();
      if ($operation == 'payin') {
        $users->condition('base_table.payways', [Wallet::PAYWAY_ANYONE_IN, Wallet::PAYWAY_ANYONE_BI], 'IN');
        $query->leftjoin('mcapi_wallet__payers', 'payers', "payers.payers_target_id = base_table.holder_entity_id");
        $users->condition('payers.payers_target_id', $uid);
      }
      elseif ($operation == 'payout') {
        $users->condition('base_table.payways', [Wallet::PAYWAY_ANYONE_OUT, Wallet::PAYWAY_ANYONE_BI], 'IN');
        $query->leftjoin('mcapi_wallet__payees', 'payees', "payees.payees_target_id = base_table.holder_entity_id");
        $users->condition('payees.payees_target_id', $uid);
      }
      $or->condition($users);
    }
    // Now ensure the wallet holder is included.
    $holder = $query->andConditionGroup();
    $holder->condition('holder_entity_type', 'user');
    $holder->condition('holder_entity_id', $uid);
    $holder->condition('base_table.payways', Wallet::PAYWAY_AUTO, '<>');
    $or->condition($holder);
    $query->condition($or);
    $query->addTag('whichWallets');
    // Allow modification as if it was an entityquery
    $query->addTag('entity_query_mcapi_wallet');
    return $query->execute()->fetchCol();
  }

}
