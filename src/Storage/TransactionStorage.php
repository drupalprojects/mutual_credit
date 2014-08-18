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

use Drupal\Core\Entity\ContentEntityDatabaseStorage;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Database\Database;
use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\mcapi\Entity\State;


class TransactionStorage extends ContentEntityDatabaseStorage implements TransactionStorageInterface {

  /**
   * because the transaction entity is keyed by serial number not xid,
   * and because it contains child entities,
   * and because we have an erase mode which does not delete the transaction,
   * We need to overwrite the whole save function
   * Also in this method we write the index table
   *
   * @see EntityStorageBase::save().
   */
  public function save(EntityInterface $entity) {
    $entity->preSave($this);
    $this->invokeHook('presave', $entity);
    //this $entity is coming from above where it may have $children
    //and in fact be several records
    //NB currently transactions are NOT revisionable
    $is_new = $entity->isNew();
    $serial = $is_new ? $this->nextSerial() : $entity->serial->value;
    $parent = 0;
    //note that this clones the parent tranaction
    foreach (mcapi_transaction_flatten($entity) as $transaction) {
      $record = $this->mapToStorageRecord($transaction);
      if (!$is_new) {
        $return = drupal_write_record('mcapi_transaction', $record, 'xid');
        $cache_ids = array($transaction->id());
        $this->indexDrop($serial);
      }
      else {
        $return = SAVED_NEW;
        // Ensure the entity is still seen as new after assigning it an id while storing its data.
        $transaction->enforceIsNew();
        $record->serial = $serial;
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
          $entity->serial->value = $serial;
        }
        // Reset general caches, but keep caches specific to certain entities.
        $cache_ids = array();
      }
      if (!$transaction->id()) mtrace();
      $this->invokeFieldMethod($is_new ? 'insert' : 'update', $transaction);
      $this->saveFieldItems($transaction, !$is_new);
      $this->resetCache($cache_ids);

      db_delete('mcapi_transactions_index')
        ->condition('xid', $insert_id)
        ->execute();
      // we only index transactions in states which are 'counted'
      if (array_key_exists($record->state, mcapi_states_counted(TRUE))) {

        $query = db_insert('mcapi_transactions_index')
          ->fields(array('xid', 'serial', 'wallet_id', 'partner_id', 'state', 'curr_id', 'volume', 'incoming', 'outgoing', 'diff', 'type', 'created', 'child'));

        foreach ($transaction->worth->getValue() as $worth) {
          $query->values(array(
            'xid' => $insert_id,
            'serial' => $record->serial,
            'wallet_id' => $record->payer,
            'partner_id' => $record->payee,
            'state' => $record->state,
            'curr_id' => $worth['curr_id'],
            'volume' => $worth['value'],
            'incoming' => 0,
            'outgoing' => $worth['value'],
            'diff' => -$worth['value'],
            'type' => $record->type,
            'created' => $record->created,
            //could this be more elegant?
            'child' => intval((bool)$record->parent)
          ));
          $query->values(array(
            'xid' => $insert_id,
            'serial' => $record->serial,
            'wallet_id' => $record->payee,
            'partner_id' => $record->payer,
            'state' => $record->state,
            'curr_id' => $worth['curr_id'],
            'volume' => $worth['value'],
            'incoming' => $worth['value'],
            'outgoing' => 0,
            'diff' => $worth['value'],
            'type' => $record->type,
            'created' => $record->created,
            'child' => intval((bool)$record->parent)
          ));
        }
        $query->execute();

      }
      // The entity is no longer new.
      $entity->enforceIsNew(FALSE);

      // Allow code to run after saving.
      $entity->postSave($this, !$is_new);
      $this->invokeHook($is_new ? 'insert' : 'update', $entity);

      // After saving, this is now the "original entity", and subsequent saves
      // will be updates instead of inserts, and updates must always be able to
      // correctly identify the original entity.
      $entity->setOriginalId($entity->id());

      unset($entity->original);
    }
    return $return;
  }


  /**
   * This storage controller deletes transactions according to settings.
   * Either by changing their state, thus retaining a record, or removing
   * the data completely
   */
  protected function doDelete($entities) {
    //first of all we need to get a flat array of all the entities.
    $indelible = \Drupal::config('mcapi.misc')->get('indelible');
    foreach ($entities as $entity) {
      foreach(mcapi_transaction_flatten($entity) as $transaction) {
        $transactions[$transaction->id()] = $transaction;
      }
    }
    //now
    foreach ($transactions as $xid => $transaction) {
      if ($indelible) {
        $transaction->set('state', TRANSACTION_STATE_UNDONE);
        $transaction->save($transaction);
        //leave the fieldAPI data as is
      }
      else {
        $deletable_entities[$xid] = $entity;
      }
      $serials[] = $entity->serial->value;
    }
    //unusually we're not going to call on the parent::doDelete()
    //we collected up all the ids so we can delete in one query
    if (!$indelible && $deletable_entities) {
      parent::doDelete($deletable_entities);
      //this does resetCache()
    }
    else {
      $this->resetCache(array_keys($transactions));
    }
    //maybe this should be in postDelete
    $this->indexDrop($serials);
  }

  /**
   * for development use only! This is not (yet in the interface)
   */
  public function wipeslate($curr_id = NULL) {
    //save loading all transactions into memory at the same time
    //I don't know a more elegant way...
    if ($curr_id) {
      $serials = db_select("mcapi_transactions_index", 't')
        ->fields('t', array('serial'))
        ->condition('curr_id', $curr_id)
        ->execute();
      $this->delete($serials->fetchCol(), TRUE);
    }
    else {
      //this will reset the xid autoincremented field
      db_truncate('mcapi_transaction')->execute();
      //and the index table
      db_truncate('mcapi_transactions_index')->execute();
    }
  }

  /**
   * @see \Drupal\mcapi\Storage\TransactionStorageInterface::indexRebuild()
   */
  public function indexRebuild() {
    $states = $this->counted_states();
    db_truncate('mcapi_transactions_index')->execute();
    db_query("
      INSERT INTO {mcapi_transactions_index} (
        SELECT
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
        FROM {mcapi_transaction} t
        RIGHT JOIN {mcapi_transaction__worth} w ON t.xid = w.entity_id
        WHERE state IN ($states)
      )"
    );
    db_query(
      "INSERT INTO {mcapi_transactions_index} (
        SELECT
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
        FROM {mcapi_transaction} t
        RIGHT JOIN {mcapi_transaction__worth} w ON t.xid = w.entity_id
        WHERE state IN ($states)
      ) "
    );
  }
  /**
   * @see \Drupal\mcapi\Storage\TransactionStorageInterface::indexCheck()
   */
  public function indexCheck() {
    if (db_query("SELECT SUM (diff) FROM {mcapi_transactions_index}")->fetchField() +0 == 0) {
      $states = $this->counted_states();
      $volume_index = db_query("SELECT sum(incoming) FROM {mcapi_transactions_index}")->fetchField();
      $volume = db_query("SELECT sum(w.worth_value)
        FROM {mcapi_transaction} t
        LEFT JOIN {mcapi_transaction__worth} w ON t.xid = w.entity_id AND t.state IN ($states)"
      )->fetchField();
      if ($volume_index == $volume) return TRUE;
    }
    return FALSE;
  }
  /**
   * @see \Drupal\mcapi\Storage\TransactionStorageInterface::indexDrop()
   */
  public function indexDrop($serials) {
    db_delete('mcapi_transactions_index')->condition('serial', (array)$serials)->execute();
  }

  /**
   * @see \Drupal\mcapi\Storage\TransactionStorageInterface::nextSerial()
   */
  protected function nextSerial() {
    //TODO: I think this needs some form of locking so that we can't get duplicate transactions.
    return db_query("SELECT MAX(serial) FROM {mcapi_transaction}")->fetchField() + 1;
  }


  /**
   * @see \Drupal\mcapi\Storage\TransactionStorageInterface::filter()
   */
  public static function filter(array $conditions = array(), $offset = 0, $limit = 0) {
    $query = db_select('mcapi_transaction', 'x')
      ->fields('x', array('xid', 'serial'))
      ->orderby('created', 'DESC');
    foreach(array('state', 'serial', 'payer', 'payee', 'creator', 'type') as $field) {
      if (array_key_exists($field, $conditions)) {
        $query->condition($field, (array)$conditions[$field]);
        unset($conditions[$field]);
      }
    }
    if (!array_key_exists('state', $conditions)) {
      $query->condition('state', mcapi_states_counted());
    }
    //TODO decide definitively whether 'involving' and 'including' are the same
    //because transactions can only ever happen within an exchange.
    //unless wallets change owners or owners move between exchanges
    if (array_key_exists('involving', $conditions)) {
      $wids = (array)$conditions['involving'];
      $query->condition(db_and()
          ->condition('payer', $wids)
          ->condition('payee', $wids)
      );
      unset($conditions['involving']);
    }
    if (array_key_exists('including', $conditions)) {
      $wids = (array)$conditions['including'];
      $query->condition(db_or()
        ->condition('payer', $wids)
        ->condition('payee', $wids)
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
    $query = db_select('mcapi_transactions_index', 'i')->fields('i', array('curr_id'));
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
    $query = db_select('mcapi_transactions_index', 'i')->fields('i', array('wallet_id'));
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
    db_query("SET @csum := 0");
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
    $query = db_select('mcapi_transaction__worth', 'w');
    $query->join('mcapi_transaction', 't', 't.xid = w.entity_id');
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
    $query = db_select('mcapi_transaction__worth', 'w');
    $query->join('mcapi_transaction', 't', 't.xid = w.entity_id');
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
      $query->conditions('state', $this->counted_states());
    }
  }

  /**
   * helper function to filter queries by counted states only
   * @return string
   *   a comma separated list of state ids, in quote marks
   */
  private function counted_states() {
    foreach (mcapi_states_counted(TRUE) as $state_id) {
      $counted_states[] = "'".$state_id."'";
    }
    return implode(', ', $counted_states);
  }

  function getSchema() {
    $schema = parent::getSchema();
    $schema['mcapi_transaction'] += array(
      'indexes' => array(
        'parent' => array('parent'),
      ),
      //drupal doesn't actually do anything with these
      'foreign keys' => array(
        'payer' => array(
          'table' => 'users',
          'columns' => array('uid' => 'uid'),
        ),
        'payee' => array(
          'table' => 'users',
          'columns' => array('uid' => 'uid'),
        )
      )
    );
    $schema['mcapi_transactions_index'] = array(
      'description' => 'currency transactions between users',
      'fields' => array(
        'xid' => array(
          'description' => 'the unique transaction ID',
          'type' => 'int',
          'size' => 'normal',
          'not null' => TRUE,
        ),
        'serial' => array(
          'description' => 'serial number (integer)',
          'type' => 'int',
          'size' => 'normal',
          'not null' => FALSE,
        ),
        'wallet_id' => array(
          'description' => 'the id of the wallet we are viewing',
          'type' => 'int',
          'size' => 'small',
          'not null' => TRUE,
        ),
        'partner_id' => array(
          'description' => 'the id of the 2nd wallet in the transaction',
          'type' => 'int',
          'size' => 'small',
          'not null' => TRUE,
        ),
        'state' => array(
          'description' => 'Completed, pending, disputed, etc',
          'type' => 'varchar',
          'length' => '16',
          'not null' => TRUE,
          'default' => TRANSACTION_STATE_FINISHED
        ),
        'type' => array(
          'description' => 'The type of transaction, types are provided by modules',
          'type' => 'varchar',
          'length' => '32',
          'not null' => TRUE,
        ),
        'created' => array(
          'description' => 'Unixtime that the transaction was recorded',
          'type' => 'int',
          'size' => 'normal',
          'not null' => TRUE,
        ),
        'curr_id' => array(
          'description' => 'The currency ID',
          'type' => 'varchar',//when to use varchar and when string?
          'length' => '8',
        ),
        'incoming' => array(
          'description' => 'Income',
          'type' => 'int',
          'size' => 'normal',
          'not null' => TRUE,
          'default' => 0
        ),
        'outgoing' => array(
          'description' => 'Outgoing',
          'type' => 'int',
          'size' => 'normal',
          'not null' => TRUE,
          'default' => 0
        ),
        'diff' => array(
          'description' => 'Change in balance',
          'type' => 'int',
          'size' => 'normal',
          'not null' => TRUE,
          'default' => 0
        ),
        'volume' => array(
          'description' => 'Volume',
          'type' => 'int',
          'size' => 'normal',
          'not null' => TRUE,
          'default' => 0
        ),
        'child' => array(
          'description' => 'whether this transaction is a child',
          'type' => 'int',
          'size' => 'small',
          'not null' => TRUE,
        ),
      ),
      'primary key' => array('xid', 'wallet_id', 'curr_id'),
      'indexes' => array(
        'wallet_id' => array('wallet_id'),
        'partner_id' => array('partner_id'),
      ),
    );
    return $schema;
  }
}


/**
 * load the transaction states and filter them according to the misc settings
 *
 * @param boolean $counted
 *
 * @return Drupal\Core\Config\Entity\ConfigEntityInterface[]
 *
 * @todo later we might want to provide a fuller interface for editing states
 * types, esp the name and description e.g. admin/accounting/workflow/states
 *
 * @todo cache this
 *
 */
function mcapi_states_counted($counted = TRUE) {
  $counted_states = \Drupal::config('mcapi.misc')->get('counted');
  foreach (State::loadMultiple() as $state) {
    if (array_key_exists($state->id, $counted_states)) {
      if ($counted_states[$state->id] == $counted) {
        $result[] = $state->id;
      }
    }
    else {
      //look at the state entities own setting if it hasn't been saved
      if ($state->counted == $counted) {
        $result[] = $state->id;
      }
    }
  }
  return $result;
}
