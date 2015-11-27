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
   * because the transaction entity is keyed by serial number not xid,
   * and because it contains child entities,
   * We need to overwrite the whole save function
   * Note therefore that preSave and postSave take the whole transaction cluster
   *
   */
  public function save(EntityInterface $transaction) {
    $this->doPreSave($transaction);
    $is_new = $transaction->isNew();
    //determine the serial number
    if ($is_new) {
      $last = $this->database->query(
        "SELECT MAX(serial) FROM {mcapi_transaction}"
      )->fetchField();
      $transaction->serial->value = $this->database->nextId($last);
    }

    $parent = 0;
    //note that flatten() clones the transactions
    foreach ($transaction->flatten(FALSE) as $entity) {
      $entity->serial->value = $transaction->serial->value;
      $record = $this->mapToStorageRecord($entity);
      $record->changed = REQUEST_TIME;

      if ($is_new) {
        $return = SAVED_NEW;
        $record->created = REQUEST_TIME;
        //the first transaction is the parent,
        //and the subsequent transactions must have its xid as their parent
        $record->parent = (int)$parent;
        
        // Ensure the entity is still seen as new after assigning it an id while storing its data.
        if (!$entity->isNew()) {
          echo 'enforcing newness of entity!';
          $entity->enforceIsNew();
        }
        $entity->xid->value = $this->database
          ->insert('mcapi_transaction', ['return' => Database::RETURN_INSERT_ID])
          ->fields((array) $record)
          ->execute();
        if (!$parent) {
          //the first entity running through here is always the parent, thus has a parent xid of 0
          $parent = $entity->xid->value;//saved for next in the flattened array
        }
        // The entity is no longer new.
        $entity->enforceIsNew(FALSE);//because we were working on a clone
        // Reset general caches, but keep caches specific to certain entities.
        $cache_ids = [];
      }
      else {
        $return = SAVED_UPDATED;
        $this->database
          ->update('mcapi_transaction')
          ->fields((array) $record)
          ->condition('xid', $record->xid)
          ->execute();
        $this->resetCache([$entity->id()]);
      }
      $this->doSaveFieldItems($entity);
    }
    
    // Allow code to run after saving.
    $this->doPostSave($transaction, !$is_new);
    $transaction->setOriginalId($transaction->id());
    unset($transaction->original);
    $this->clearCache([$transaction]);
    return $return;
  }


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
