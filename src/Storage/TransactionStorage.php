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

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Database\Database;
use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\Entity\State;


class TransactionStorage extends TransactionIndexStorage {

  /**
   * because the transaction entity is keyed by serial number not xid,
   * and because it contains child entities,
   * We need to overwrite the whole save function
   * Also in this method we write the index table
   *
   * @see EntityStorageBase::save().
   */

  public function save(EntityInterface $entity) {
    $this->invokeHook('presave', $entity);
    //this $entity is coming from above where it may have $children
    //and in fact be several records
    //NB currently transactions are NOT revisionable
    if ($entity->isNew()) {
      $entity->serial->value = $this->nextSerial();
    }
    else echo 'not new';
    $parent = 0;
    //note that this clones the parent tranaction
    foreach ($entity->flatten() as $transaction) {
      $transaction->serial = $entity->serial->value;
      $record = $this->mapToStorageRecord($transaction);
      $record->changed = REQUEST_TIME;
      
      if (!$entity->isNew()) {
        $return = SAVED_UPDATED;
        $this->database
          ->update('mcapi_transaction')
          ->fields((array) $record)
          ->condition('xid', $record->xid)
          ->execute();
        $cache_ids = array($transaction->id());
        $this->resetCache($cache_ids);
        $this->indexDrop($serial);
        $this->invokeFieldMethod('update', $transaction);
        $this->invokeHook('update', $entity);
      }
      else {
        $return = SAVED_NEW;
        // Ensure the entity is still seen as new after assigning it an id while storing its data.
        $transaction->enforceIsNew();
        //$record->serial = $serial;
        //the first transaction is the parent,
        //and the subsequent transactions must have its xid as their parent
        if ($parent) $record->parent = $parent;
        $insert_id = $this->database
        ->insert('mcapi_transaction', array('return' => Database::RETURN_INSERT_ID))
        ->fields((array) $record)
        ->execute();
        $transaction->xid->value = $insert_id;
        if (!$parent) {
          //alter the passed entity, at least the parent
          $parent = $entity->xid->value = $insert_id;
        }

        $this->invokeFieldMethod('insert', $transaction);
        // The entity is no longer new.
        $entity->enforceIsNew(FALSE);
        $transaction->setOriginalId($entity->id());
        $this->invokeHook('insert', $entity);
        // Reset general caches, but keep caches specific to certain entities.
        $cache_ids = array();
      }
      $this->saveFieldItems($transaction, $return == SAVED_UPDATED);
echo 'saved fields';
    }
    // Allow code to run after saving.
    $entity->postSave($this, $return == SAVED_UPDATED);
echo 'postsaved';
    unset($entity->original);

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function doErase(array $transactions) {
    foreach ($transactions as $transaction) {
      //its only necessary to set the state of the parent
      $transaction->set('state', TRANSACTION_STATE_ERASED);
      $transaction->save($transaction);
    }
  }


  /**
   * for development use only!
   */
  public function wipeslate($curr_id = NULL) {
    $serials = parent::wipeslate($curr_id);
    $this->delete($serials, TRUE);
    //reset the entity table
    $this->database->truncate('mcapi_transaction')->execute();
  }

  /**
   * @see \Drupal\mcapi\Storage\TransactionStorageInterface::filter()
   * @note the parent function is very similar but works on the index table and doesn't know payer and payee conditions.
   */
  public static function filter(array $conditions = array(), $offset = 0, $limit = 0) {
    $query = db_select('mcapi_transaction', 'x')
      ->fields('x', array('xid', 'serial'))
      ->orderby('created', 'DESC');
    //take account for the worth table.
    if (array_key_exists('value', $conditions) || array_key_exists('curr_id', $conditions)) {
      $query->join('mcapi_transaction__worth', 'w', 'x.xid = w.entity_id');
      if (array_key_exists('value', $conditions)) {
        $conditions['w.worth_value'] = $conditions['value'];
      }
      if (array_key_exists('curr_id', $conditions)) {
        $conditions['w.worth_curr_id'] = $conditions['curr_id'];
      }
    }
    //TODO generalise this to take account of other fieldAPI fields?
    $conditions + array(
      'state' => mcapi_states_counted()
    );
debug($conditions);
    foreach($conditions as $field => $value) {
      switch($field) {
      	case 'state':
      	case 'serial':
      	case 'payer':
      	case 'payee':
      	case 'creator':
      	case 'type':
      	case 'w.worth_value':
      	case 'w.worth_curr_id':
      	  $query->condition($field, (array)$value);
      	  break;
      	case 'involving':
      	  $value = (array)$value;
      	  $cond_group = count($value) == 1 ? db_or() : db_and();
      	  $query->condition($cond_group
    	      ->condition('payer', $value)
    	      ->condition('payee',$value)
      	  );
          break;
      	case 'from':
          $query->condition('created', $value, '>');
          break;
      	case 'to':
          $query->condition('created', $value, '<');
          break;
      }
      unset($conditions[$field]);
    }
    if ($limit) {
      //assume that nobody would ask for unlimited offset results
      $query->range($offset, $limit);
    }
    return $query->execute()->fetchAllKeyed();
  }

}
