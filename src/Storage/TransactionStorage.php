<?php

namespace Drupal\mcapi\Storage;

use Drupal\Core\Entity\EntityInterface;

/**
 * Storage controller for Transaction entity.
 */
class TransactionStorage extends TransactionIndexStorage {

  /**
   * {@inheritdoc}
   */
  public static function loadBySerial($serial, $exception_fail = FALSE) {
    $transactions = \Drupal::entityTypeManager()
      ->getStorage('mcapi_transaction')
      ->loadByProperties(['serial' => $serial]);
    ksort($transactions);
    if ($transactions) {
      $transaction = array_shift($transactions);
      $transaction->children = $transactions;
      return $transaction;
    }
    if ($exception_fail) {
      throw new \Exception('No transaction with serial '.$serial);
    }
  }


  /**
   * {@inheritdoc}
   *
   * Because the transaction entity is keyed by serial number not xid,
   * and because it contains child entities,
   * We need to overwrite the whole save function
   * and by the time we call the parent, we pass it individual transaction
   * entities having called $transaction->flatten.
   */
  public function save(EntityInterface $transaction) {
    // Determine the serial number.
    if (empty($transaction->serial->value)) {
      $serial = $this->database->query(
        "SELECT MAX(serial) FROM {mcapi_transaction}"
      )->fetchField() + 1;
    }
    else {
      $serial = $transaction->serial->value;
    }
    $parent = 0;
    // Note that flatten() clones the transactions.
    foreach ($transaction->flatten(FALSE) as $entity) {
      $entity->serial->value = $serial;
      // Entity parent is 0 for the first one and then the xid of the first one
      // for all subsequent ones.
      $entity->parent->value = $parent;
      $return = parent::save($entity);
      if ($parent == 0) {
        $parent = $entity->id();
      }
    }

    $transaction->serial->value = $serial;
    // Allow code to run after saving.
    $transaction->setOriginalId($transaction->id());
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function doSave($id, EntityInterface $entity) {
    $record = $this->mapToStorageRecord($entity);
    $record->changed = REQUEST_TIME;
    $return = parent::doSave($entity->xid->value, $entity);
    // The entity is no longer new.
    // because we were working on a clone.
    $entity->enforceIsNew(FALSE);

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCache($transactions = []) {
    $ids = [];
    foreach ($transactions as $transaction) {
      foreach ($transaction->flatten() as $t) {
        $ids[] = $transaction->id();
        $t->payer->entity->invalidateTagsOnSave(TRUE);
        $t->payee->entity->invalidateTagsOnSave(TRUE);
      }
    }
    $this->resetCache($ids);
  }

}
