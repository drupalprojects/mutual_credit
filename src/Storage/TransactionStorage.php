<?php

/**
 * @file
 * Contains \Drupal\mcapi\Storage\TransactionStorage.
 * All transaction storage works with individual Drupalish entities and the xid key
 * Only at a higher level do transactions have children and work with serial numbers
 *
 * this sometimes uses sql for speed rather than the Drupal DbAPI
 *
 */

namespace Drupal\mcapi\Storage;

use Drupal\Core\Entity\ContentEntityDatabaseStorage;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\mcapi\Entity\TransactionInterface;

class TransactionStorage extends ContentEntityDatabaseStorage implements TransactionStorageInterface {

  /**
   *
   */
  public function postLoad(array &$entities) {
    foreach ($entities as $transaction) {
      if ($transaction->parent->value == 0) {
        $transaction->children = array();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doSave($id, EntityInterface $entity) {
    //this $entity is coming from above where it may have $children
    //and in fact be several records
    //so we overwrite the function in order to save the children as separate records
    //NB currently transactions are NOT revisionable
    $is_new = $entity->isNew();
    $serial = $is_new ? $this->nextSerial() : $entity->serial->value;
    $parent = 0;
    //note that this clones the parent tranaction
    foreach (mcapi_transaction_flatten($entity) as $transaction) {
      $record = $this->mapToStorageRecord($transaction);
      if (!$is_new) {
        $return = drupal_write_record('mcapi_transactions', $record, 'xid');
        $cache_ids = array($transaction->id());
        $this->indexDrop($serial);
      }
      else {
        // Ensure the entity is still seen as new after assigning it an id while storing its data.
        $transaction->enforceIsNew();
        $record->serial = $serial;
        //the first transaction is the parent,
        //and the subsequent transactions must have its xid as their parent
        if ($parent) $record->parent = $parent;
        $return = drupal_write_record('mcapi_transactions', $record);
        $transaction->xid = $record->xid;
        if (!$parent) {
          $parent = $record->xid;
          //alter the original parent
          $entity->xid = $record->xid;
          $entity->serial = $serial;
        }
        // Reset general caches, but keep caches specific to certain entities.
        $cache_ids = array();
      }
      $this->invokeFieldMethod($is_new ? 'insert' : 'update', $transaction);
      $this->saveFieldItems($transaction, !$is_new);
      $this->resetCache($cache_ids);
      $this->addIndex($record, $transaction->get('worth')->getValue());
    }

    return $return;
  }


  /**
   * This storage controller deletes transactions according to settings.
   * Either by changing their state, thus retaining a record, or removing
   * the data completely
   * //TODO buid in an optional HARD delete, ei
   */
  public function doDelete($entities) {
    $indelible = \Drupal::config('mcapi.misc')->get('indelible');
    foreach ($entities as $entity) {
      foreach(mcapi_transaction_flatten($entity) as $transaction) {
        if ($indelible) {
          $transaction->set('state', TRANSACTION_STATE_UNDONE);
          $transaction->save($transaction);
          //leave the fieldAPI data as is
        }
        else {
          $ids[] = $entity->xid->value;
          $this->invokeFieldMethod('delete', $transaction);
          $this->deleteFieldItems($transaction);
        }
      }
      $serials[] = $entity->serial->value;
    }
    $this->indexDrop($serials);
    //we collected up all the ids so we can delete in one query
    if (!$indelible && $ids) {
      $this->database->delete('mcapi_transactions')->condition('xid', $ids)->execute();
    }
  }

  /**
   * for development use only! This is not (yet in the interface)
   */
  public function wipeslate($curr_id = NULL) {
    //save loading all transactions into memory at the same time
    //I don't know a more elegant way...
    $serials = db_select("mcapi_transactions_index", 't')
      ->fields('t', array('serial'))
      ->condition('curr_id', $curr_id)
      ->execute();
    $this->delete($serials->fetchCol(), TRUE);
    if (is_null($curr_id)) {
      //this will reset the xid autoincremented field
      $this->database->delete('mcapi_transactions')->execute();
      //and the index table
      $this->database->delete('mcapi_transactions_index')->execute();
    }
  }

  /**
   * @see \Drupal\mcapi\Storage\TransactionStorageInterface::addIndex()
   */
  public function addIndex(\stdClass $record, array $worths) {

    $this->database->delete('mcapi_transactions_index')
      ->condition('xid', $record->xid)
      ->execute();
    // we only index transactions with positive state values
    if ($record->state < 1) {
      return;
    };
    $query = $this->database->insert('mcapi_transactions_index')
      ->fields(array('xid', 'serial', 'wallet_id', 'partner_id', 'state', 'curr_id', 'volume', 'incoming', 'outgoing', 'diff', 'type', 'created', 'child'));

    foreach ($worths as $curr_id => $value) {
      $query->values(array(
        'xid' => $record->xid,
        'serial' => $record->serial,
        'wallet_id' => $record->payer,
        'partner_id' => $record->payee,
        'state' => $record->state,
        'curr_id' => $curr_id,
        'volume' => $value,
        'incoming' => 0,
        'outgoing' => $value,
        'diff' => -$value,
        'type' => $record->type,
        'created' => $record->created,
        //could this be more elegant?
      	'child' => intval((bool)$record->parent)
      ));
      $query->values(array(
        'xid' => $record->xid,
        'serial' => $record->serial,
        'wallet_id' => $record->payee,
        'partner_id' => $record->payer,
        'state' => $record->state,
        'curr_id' => $curr_id,
        'volume' => $value,
        'incoming' => $value,
        'outgoing' => 0,
        'diff' => $value,
        'type' => $record->type,
        'created' => $record->created,
      	'child' => intval((bool)$record->parent)
      ));
    }
    $query->execute();
  }
  /**
   * @see \Drupal\mcapi\Storage\TransactionStorageInterface::indexRebuild()
   */
  public function indexRebuild() {
    $this->database->truncate('mcapi_transactions_index')->execute();
    $this->database->query("INSERT INTO {mcapi_transactions_index} (SELECT
        t.xid,
    		t.serial,
        t.payer AS wallet_id,
        t.payee AS partner_id,
        t.state,
        t.type,
        t.created,
        worth_curr_id,
        0 AS incoming,
        worth_value AS outgoing,
        - worth_value AS diff,
        worth_value AS volume,
    		t.parent as child
      FROM {mcapi_transactions} t
      RIGHT JOIN {mcapi_transaction__worth} ON t.xid = w.xid
      WHERE state > 0) "
    );
    $this->database->query("INSERT INTO {mcapi_transactions_index} (SELECT
        t.xid,
    		t.serial,
        t.payee AS wallet_id,
        t.payer AS partner_id,
        t.state,
        t.type,
        t.created,
        worth_curr_id,
        worth_value AS incoming,
        0 AS outgoing,
        worth_value AS diff,
        worth_value AS volume,
    		t.parent as child
      FROM {mcapi_transactions} t
      RIGHT JOIN {mcapi_transaction__worth} ON t.xid = w.xid
      WHERE state > 0) "
    );
  }
  /**
   * @see \Drupal\mcapi\Storage\TransactionStorageInterface::indexCheck()
   */
  public function indexCheck() {
    if ($this->database->query("SELECT SUM (diff) FROM {mcapi_transactions_index}")->fetchField() +0 == 0) {
      $volume_index = db_query("SELECT sum(incoming) FROM {mcapi_transactions_index}")->fetchField();
      $volume = db_query("SELECT sum(value) FROM {mcapi_transactions} t LEFT JOIN {mcapi_transactions_worths} w ON t.xid = w.xid AND t.state > 0")->fetchField();
      if ($volume_index == $volume) return TRUE;
    }
    return FALSE;
  }
  /**
   * @see \Drupal\mcapi\Storage\TransactionStorageInterface::indexDrop()
   */
  public function indexDrop($serials) {
    $this->database->delete('mcapi_transactions_index')->condition('serial', (array)$serials)->execute();
  }

  /**
   * @see \Drupal\mcapi\Storage\TransactionStorageInterface::nextSerial()
   */
  public function nextSerial() {
    //TODO: I think this needs some form of locking so that we can't get duplicate transactions.
    return $this->database->query("SELECT MAX(serial) FROM {mcapi_transactions}")->fetchField() + 1;
  }


  /**
   * @see \Drupal\mcapi\Storage\TransactionStorageInterface::filter()
   */
  public function filter(array $conditions = array(), $offset = 0, $limit = 0) {
    $query = $this->database->select('mcapi_transactions', 'x')
      ->fields('x', array('xid', 'serial'))
      ->orderby('created', 'DESC');
    foreach(array('state', 'serial', 'payer', 'payee', 'creator', 'type') as $field) {
      if (array_key_exists($field, $conditions)) {
        $query->condition($field, (array)$conditions[$field]);
        unset($conditions[$field]);
      }
    }
    if (!array_key_exists('state', $conditions)) {
      $query->condition('state', 0, '>');
    }

    if (array_key_exists('involving', $conditions)) {
      $query->condition(db_or()
        ->condition('payer', (array)$conditions['involving'])
        ->condition('payee', (array)$conditions['involving'])
      );
      unset($conditions['involving']);
    }
    if (array_key_exists('from', $conditions)) {
      $query->condition('created', $conditions['from'], '>');
      unset($conditions['from']);
    }
    if (array_key_exists('to', $conditions)) {
      $query->condition('created', $conditions['to'], '<');
      unset($conditions['to']);
    }

    if (array_key_exists('curr_id', $conditions) || array_key_exists('value', $conditions)) {
      $query->join('mcapi_transaction__worth', 'w', 'x.xid = w.entity_id');
      foreach (array('curr_id', 'value') as $field) {
        if (array_key_exists($field, $conditions)) {
          $query->condition('worth_'. $field, $conditions[$field]);
        }
      }
    }

    if ($limit) {
      //assume that nobody would ask for unlimited offset results
      $query->range($offset, $limit);
    }
    return $query->execute()->fetchAllKeyed();
  }

  /**
   * @see \Drupal\mcapi\Storage\TransactionStorageInterface::summaryData()
   */
  public function summaryData($wallet_id, array $conditions = array()) {
    //TODO We need to return 0 instead of null for empty columns
    //then get rid of the last line of this function
    $query = $this->database->select('mcapi_transactions_index', 'i')->fields('i', array('curr_id'));
    $query->addExpression('COUNT(DISTINCT i.serial)', 'trades');
    $query->addExpression('SUM(i.incoming)', 'gross_in');
    $query->addExpression('SUM(i.outgoing)', 'gross_out');
    $query->addExpression('SUM(i.diff)', 'balance');
    $query->addExpression('SUM(i.volume)', 'volume');
    $query->addExpression('COUNT(DISTINCT i.partner_id)', 'partners');
    $query->condition('i.wallet_id', $wallet_id)
      ->groupby('curr_id');
    $this->parseConditions($query, $conditions);
    $result = $query->execute()->fetchAllAssoc('curr_id', \PDO::FETCH_ASSOC);
    //if ($result)
      return $result;
    //return array('curr_id' =>);
  }

  //experimental
  public function balances ($curr_id, $wids = array(), array $conditions = array()) {
    $query = $this->database->select('mcapi_transactions_index', 'i')->fields('i', array('wallet_id'));
    $query->addExpression('SUM(i.diff)', 'balance');
    if ($wids) {
      $query->condition('i.wallet_id', $wids);
    }
    $query->condition('i.curr_id', $curr_id)
    ->groupby('curr_id');
    $this->parseConditions($query, $conditions);
    return $query->execute()->fetchAllKeyed();
  }

  /**
   * @see \Drupal\mcapi\Storage\TransactionStorageInterface::timesBalances()
   */
  public function timesBalances($wallet_id, $curr_id, $since = 0) {
    //TODO cache this, and clear the cache whenever a transaction changes state or is deleted
    //this is a way to add up the results as we go along
    $this->database->query("SET @csum := 0");
    //I wish there was a better way to do this.
    //It is cheaper to do stuff in mysql
    $all_balances = $this->database->query(
      "SELECT created, (@csum := @csum + diff) as balance
        FROM {mcapi_transactions_index}
        WHERE wallet_id = $wallet_id AND curr_id = '$curr_id'
        ORDER BY created"
    )->fetchAll();
    $history = array();
    //having done the addition, we can chop the beginning off the array
    //if two transactions happen on the same second, the latter running balance will be shown only
    foreach ($all_balances as $point) {
      //@todo this could be optimised since we could be running through a lot of points in chronological order
      //we just need to remove all array values with keys smaller than $since.
      //I think it can't be done in the SQL coz we need them for adding up.
      if ($point->created < $since) continue;
      $history[$point->created] = $point->balance;
    }
    return $history;
  }

  /**
   * @see \Drupal\mcapi\Storage\TransactionStorageInterface::count()
   */
  public function count($curr_id = '', $conditions = array(), $serial = FALSE) {
    $query = $this->database->select('mcapi_transaction__worth', 'w');
    $query->join('mcapi_transactions', 't', 't.xid = w.entity_id');
    $query->addExpression('count(w.entity_id)');
    if ($curr_id) {
      $query->condition('w.worth_curr_id', $curr_id);
    }
    $this->parseConditions($query, $conditions);
    return $query->execute()->fetchField();
  }

  /**
   * @see \Drupal\mcapi\Storage\TransactionStorageInterface::volume()
   */
  public function volume($curr_id, $conditions = array()) {
    $query = $this->database->select('mcapi_transaction__worth', 'w');
    $query->join('mcapi_transactions', 't', 't.xid = w.entity_id');
    $query->addExpression('SUM(w.worth_value)');
    $query->condition('w.worth_curr_id', $curr_id);
    $this->parseConditions($query, $conditions);
    return $query->execute()->fetchField();
  }

  /**
   * Add an array of conditions to the select query
   *
   * @param SelectInterface $query
   * @param array $conditions
   */
  private function parseConditions(SelectInterface $query, array $conditions) {
    foreach ($conditions as $fieldname => $value) {
      $query->conditions($fieldname, $value);
    }
    if (!in_array('state', $conditions) || !is_null($conditions['state'])) {
      $query->conditions('state', '0', '>');
    }
  }
}
