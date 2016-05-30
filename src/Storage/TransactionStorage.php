<?php

/**
 * @file
 * Contains \Drupal\mcapi\Storage\TransactionStorage.
 *
 * All transaction storage works with individual Drupalish entities and the xid key
 * Only at a higher level do transactions have children and work with serial numbers
 *
 * this sometimes uses sql for speed rather than the Drupal DbAPI
 *
 */

namespace Drupal\mcapi\Storage;

use Drupal\Core\Entity\EntityInterface;


class TransactionStorage extends TransactionIndexStorage {

  /**
   * {@inheritdoc}
   *
   * Because the transaction entity is keyed by serial number not xid,
   * and because it contains child entities,
   * We need to overwrite the whole save function
   * and by the time we call the parent, we pass it individual transaction
   * entities having called $transaction->flatten
   *
   */
  public function save(EntityInterface $transaction) {
    //determine the serial number
    if ($transaction->isNew()) {
      $last = $this->database->query(
        "SELECT MAX(serial) FROM {mcapi_transaction}"
      )->fetchField();
      $serial = $this->database->nextId($last);
    }
    else {
      $serial = $transaction->serial->value;
    }
    $parent = 0;
    //note that flatten() clones the transactions
    foreach ($transaction->flatten(FALSE) as $entity) {
      $entity->serial->value = $serial;
      //entity parent is 0 for the first one and then the xid of the first one for all subsequent ones
      $entity->parent->value = $parent;
      $return = parent::save($entity);
      if ($parent == 0) {
        $parent = $entity->id();
      }
    }

    $transaction->serial->value = $serial;
    // Allow code to run after saving.
    $transaction->setOriginalId($transaction->id());
    unset($transaction->original);
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
    $entity->enforceIsNew(FALSE);//because we were working on a clone

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCache($transactions = []) {
    $ids = [];
    foreach ($transactions AS $transaction) {
      foreach ($transaction->flatten() as $t) {
        $ids[] = $transaction->id();
        $t->payer->entity->invalidateTagsOnSave(TRUE);
        $t->payee->entity->invalidateTagsOnSave(TRUE);
      }
    }
    $this->resetCache($ids);
  }

}
