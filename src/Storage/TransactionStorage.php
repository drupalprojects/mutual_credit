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
use Drupal\Core\Database\Database;


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

    if (!$id) {//save new
      $record->created = REQUEST_TIME;

      // Ensure the entity is still seen as new after assigning it an id while storing its data.
      $entity->enforceIsNew();
      $entity->xid->value = $this->database
        ->insert('mcapi_transaction', ['return' => Database::RETURN_INSERT_ID])
        ->fields((array) $record)
        ->execute();
    }
    else {//save updated
      $this->database
        ->update('mcapi_transaction')
        ->fields((array) $record)
        ->condition('xid', $record->xid)
        ->execute();
    }
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

  /**
   * for development use only!
   */
  public function wipeslate($curr_id = NULL) {
    $serials = parent::wipeslate($curr_id);
    $this->delete($serials, TRUE);
    //reset the entity table
    $this->database->truncate('mcapi_transaction')->execute();
    $this->clearCache();
  }

  /**
   * {@inheritdoc}
   *
   * @note the parent function is very similar but works on the index table and doesn't know payer and payee conditions.
   */
  public function filter(array $conditions = [], $offset = 0, $limit = 0) {
    $query = $this->database->select('mcapi_transaction', 'x')
      ->fields('x', array('xid', 'serial'))
      ->orderby('created', 'DESC');
    $this->parseConditions($query, $conditions);
    if ($limit) {
      //assume that nobody would ask for unlimited offset results
      $query->range($offset, $limit);
    }
    return $query->execute()->fetchAllKeyed();
  }

  /**
   * utility function to convert $conditions to modifications on the mcapi_transaction table
   */
  private function parseConditions($query, $conditions) {
    //take account for the worth table.
    if (array_key_exists('value', $conditions) || array_key_exists('curr_id', $conditions)) {
      $query->join('mcapi_transaction__worth', 'w', 'x.xid = w.entity_id');
    }
    
    $conditions += ['state' => Self::countedStates()];
    foreach($conditions as $field => $value) {
      if (!$value) continue;
      switch($field) {
      	case 'xid':
      	case 'state':
      	case 'serial':
      	case 'payer':
      	case 'payee':
      	case 'creator':
      	case 'state':
      	case 'type':
            $query->condition($field.'[]', (array)$value);
      	  break;
      	case 'involving':
      	  $value = (array)$value;
      	  $cond_group = count($value) == 1 ? db_or() : db_and();
      	  $query->condition($cond_group
    	      ->condition('payer[]', $value)
    	      ->condition('payee[]', $value)
      	  );
          break;
      	case 'curr_id':
          $query->condition('w.worth_curr_id[]', (array)$value);
          break;
      	case 'since':
          $query->condition('created', $value, '>');
          break;
      	case 'until':
          $query->condition('created', $value, '<');
          break;
      	case 'value':
          $query->condition('w.worth_value', $value);
          break;
        case 'min':
          $query->condition('w.worth_value', $value, '>=');
          break;
        case 'max':
          $query->condition('w.worth_value', $value, '<=');
          break;
      	default:
          drupal_set_message('filtering on unknown field: '.$field);
      }
    }
  }
  

}
