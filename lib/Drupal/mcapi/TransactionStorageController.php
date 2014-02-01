<?php

/**
 * @file
 * Contains \Drupal\mcapi\TransactionStorageController.
 * this sometimes uses sql for speed rather than the Drupal DbAPI
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\FieldableDatabaseStorageController;
use Drupal\mcapi\Plugin\field\field_type\Worth;

class TransactionStorageController extends FieldableDatabaseStorageController implements TransactionStorageControllerInterface {

  /**
   * {@inheritdoc}
   */
  function postLoad(array &$queried_entities) {
    $result = $this->database->query('SELECT * FROM {mcapi_transactions_worths} WHERE xid IN (:xids)', array(':xids' => array_keys($queried_entities)));
    foreach ($result as $record) {
      $queried_entities[$record->xid]->worths[$record->currcode] = array(
        'currcode' => $record->currcode,
        'value' => $record->value,
      );
    }
    /*
    // Load all the children
    $result = $this->database->query('SELECT xid FROM {mcapi_transactions} WHERE parent IN (:parents)', array(':parents' => array_keys($queried_entities)));
    foreach ($result as $record) {
      $queried_entities[$record->xid]->children[$record->xid] = NULL;
    }
    */
    parent::postLoad($queried_entities);
  }

  /*
   * In the default storage controller, 'delete' is more like 'erase', merely changing the transaction state.
   * Would be very easy to make another storage controller where delete either deletes the entity
   * Or even creates another transaction going in the opposite direction
   */
  public function delete(array $transactions) {
    foreach ($transactions as $transaction) {
      $transaction->set('state', TRANSACTION_STATE_UNDONE);
      try{
        $transaction->save($transaction);
        $this->indexDrop($transaction->serial->value);
      }
      catch (Exception $e){
        drupal_set_message(t('Failed to undo transaction: @message', array('@message' => $e->getMessage())));
      }
    }
    //TODO need to run a hook here
  }
  /*
   * How to delete the whole entity.
      $this->database->delete('mcapi_transactions_worths')
      ->condition('xid', $transaction->id())
      ->execute();
      //and the index table
      $this->database->delete('mcapi_transactions_index')
      ->condition('xid', $transaction->id())
      ->execute();
    }
    *
    *How to create another transaction going in the opposite direction
    *This gets messy... and the below is incomplete
      $parent_xid = $transaction->xid;
  	  $cluster = mcapi_transaction_flatten($this);
  	  //add reverse transactions to the children and change the state.
  	  //TODO how do we handle the attached fields? for cloned transactions? do they matter?
  	  //TODO what about undoing pending transactions? Does reverse mode make sense
  	  foreach ($cluster as $transaction) {
  	    $reversed = clone $this;
  	    $reversed_parent = $parent_xid;
  	    $reversed->payer = $this->payee;
  	    $reversed->payee = $this->payer;
  	    $reversed->type = 'reversal';
  	    unset($reversed->created, $reversed->xid);
  	    $reversed->description = t('Reversal of: @label', array('@label' => $entity['label callback']($transaction)));
  	    $this->children[] = $reversed;
  	  }
   * in both cases don't forget to remove it from the index table
   * $this->indexDrop($transaction->serial->value);
   * and don't forget that transactions in different states may be undone differently
   */



  /**
   * Save the Transaction Worth values, one per currency, to the worths table.
   *
   * @param Drupal\mcapi\TransactionInterface $transaction
   *  Transaction currently being saved.
   */
  public function saveWorths(TransactionInterface $transaction) {
    $this->database->delete('mcapi_transactions_worths')
      ->condition('xid', $transaction->id())
      ->execute();
    $query = $this->database->insert('mcapi_transactions_worths')
      ->fields(array('xid', 'currcode', 'value'));
    foreach ($transaction->worths[0] as $worth) {
      if (!$worth->value) {
        continue;
      };
      $query->values(array(
        'xid' => $transaction->id(),
        'currcode' => $worth->currcode,
        'value' => $worth->value,
      ));
    }
    $query->execute();

    //TODO fire hooks?
    //transaction_update($op, $transaction, $values);
  }

  /**
   * @see \Drupal\mcapi\TransactionStorageControllerInterface::addIndex()
   */
  public function addIndex(TransactionInterface $transaction) {

    $this->database->delete('mcapi_transactions_index')
      ->condition('xid', $transaction->id())
      ->execute();
    // we only index transactions with positive state values
    if ($transaction->state->value < 1) {
      return;
    };
    $query = $this->database->insert('mcapi_transactions_index')
      ->fields(array('xid', 'serial', 'wallet_id', 'partner_id', 'state', 'currcode', 'volume', 'incoming', 'outgoing', 'diff', 'type', 'created', 'child'));

    foreach ($transaction->worths[0] as $worth) {
      $query->values(array(
        'xid' => $transaction->id(),
        'serial' => $transaction->serial->value,
        'wallet_id' => $transaction->payer->value,
        'partner_id' => $transaction->payee->value,
        'state' => $transaction->state->value,
        'currcode' => $worth->currcode,
        'volume' => $worth->value,
        'incoming' => 0,
        'outgoing' => $worth->value,
        'diff' => -$worth->value,
        'type' => $transaction->type->value,
        'created' => $transaction->created->value,
        'exchange' => $transaction->get('exchange'),
      	'child' => !$transaction->parent->value
      ));
      $query->values(array(
        'xid' => $transaction->id(),
        'serial' => $transaction->serial->value,
        'wallet_id' => $transaction->payee->value,
        'partner_id' => $transaction->payer->value,
        'state' => $transaction->state->value,
        'currcode' => $worth->currcode,
        'volume' => $worth->value,
        'incoming' => $worth->value,
        'outgoing' => 0,
        'diff' => $worth->value,
        'type' => $transaction->type->value,
        'created' => $transaction->created->value,
        'exchange' => $transaction->get('exchange'),
      	'child' => !$transaction->parent->value
      ));
    }
    $query->execute();
  }
  /**
   * @see \Drupal\mcapi\TransactionStorageControllerInterface::indexRebuild()
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
        w.currcode,
        0 AS incoming,
        w.value AS outgoing,
        - w.value AS diff,
        w.value AS volume,
    		t.exchange,
    		t.parent as child
      FROM {mcapi_transactions} t
      RIGHT JOIN {mcapi_transactions_worths} w ON t.xid = w.xid
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
        w.currcode,
        w.value AS incoming,
        0 AS outgoing,
        w.value AS diff,
        w.value AS volume,
    		t.exchange,
    		t.parent as child
      FROM {mcapi_transactions} t
      RIGHT JOIN {mcapi_transactions_worths} w ON t.xid = w.xid
      WHERE state > 0) "
    );
  }
  /**
   * @see \Drupal\mcapi\TransactionStorageControllerInterface::indexCheck()
   */
  public function indexCheck() {
    if ($this->database->query("SELECT SUM (diff) FROM {mcapi_transactions_index}")->fetchField() +0 == 0) {
      $volume_index = db_query("SELECT sum(incoming) FROM {mcapi_transactions_index}")->fetchField();
      $volume = db_query("SELECT sum(value) FROM {mcapi_transactions} t LEFT JOIN {mcapi_transactions_worths} w ON t.xid = w.xid AND t.state > 0")->fetchField();
      debug("$volume_index == $volume");
      if ($volume_index == $volume) return TRUE;
    }
    return FALSE;
  }
  /**
   * @see \Drupal\mcapi\TransactionStorageControllerInterface::indexDrop()
   */
  public function indexDrop($serial) {
    $this->database->delete('mcapi_transactions_index')->condition('serial', $serial)->execute();
  }

  /**
   * @see \Drupal\mcapi\TransactionStorageControllerInterface::nextSerial()
   */
  public function nextSerial(TransactionInterface $transaction) {
    //TODO: I think this needs some form of locking so that we can't get duplicate transactions.
    $transaction->serial->value = $this->database->query("SELECT MAX(serial) FROM {mcapi_transactions}")->fetchField() + 1;
  }


  /**
   * @see \Drupal\mcapi\TransactionStorageControllerInterface::filter()
   */
  public function filter(array $conditions, $offset = 0, $limit = 0) {
    $query = $this->database->select('mcapi_transactions', 'x')
      ->fields('x', array('xid', 'serial'))
      ->orderby('created', 'DESC');

    foreach(array('state', 'serial', 'payer', 'payee', 'creator', 'type') as $field) {
      if (array_key_exists($field, $conditions)) {
        $query->condition($field, (array)$conditions[$field]);
        unset($conditions[$field]);
      }
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

    if (array_key_exists('currcode', $conditions) || array_key_exists('value', $conditions)) {
      $query->join('mcapi_transactions_worths', 'w', 'x.xid = w.xid');
      if (array_key_exists('currcode', $conditions)) {
        $query->condition('currcode', $conditions['currcode']);
      }
      if (array_key_exists('quantity', $conditions)) {
        $query->condition('quantity', $conditions['quantity']);
      }
    }

    if ($limit) {
      //assume that nobody would ask for unlimited offset results
      $query->range($offset, $limit);
    }
    return $query->execute()->fetchAllKeyed();
  }

  /**
   * @see \Drupal\mcapi\TransactionStorageControllerInterface::summaryData()
   */
  public function summaryData($wallet, array $conditions) {
    //TODO We need to return 0 instead of null for empty columns
    //then get rid of the last line of this function
    $query = $this->database->select('mcapi_transactions_index', 'i')->fields('i', array('currcode'));
    $query->addExpression('COUNT(DISTINCT i.serial)', 'trades');
    $query->addExpression('SUM(i.incoming)', 'gross_in');
    $query->addExpression('SUM(i.outgoing)', 'gross_out');
    $query->addExpression('SUM(i.diff)', 'balance');
    $query->addExpression('SUM(i.volume)', 'volume');
    $query->addExpression('COUNT(DISTINCT i.partner_id)', 'partners');
    $query->condition('i.wallet_id', $wallet->id())
      ->groupby('currcode');
    $this->parseConditions($query, $conditions);
    return $query->execute()->fetchAllAssoc('currcode', \PDO::FETCH_ASSOC);
    }

  //experimental
  public function balances ($currcode, $wids = array(), array $conditions) {
    $query = $this->database->select('mcapi_transactions_index', 'i')->fields('i', array('wallet_id'));
    $query->addExpression('SUM(i.diff)', 'balance');
    if ($wids) {
      $query->condition('i.wallet_id', $wids);
    }
    $query->condition('i.currcode', $currcode)
    ->groupby('currcode');
    $this->parseConditions($query, $conditions);
    return $query->execute()->fetchAllKeyed();
  }

  /**
   * @see \Drupal\mcapi\TransactionStorageControllerInterface::timesBalances()
   */
  public function timesBalances(AccountInterface $account, CurrencyInterface $currency, $since = 0) {
    //TODO cache this, and clear the cache whenever a transaction changes state or is deleted
    //this is a way to add up the results as we go along
    $this->database->query("SET @csum := 0");
    //I wish there was a better way to do this.
    //It is cheaper to do stuff in mysql
    $all_balances = $this->database->query(
      "SELECT created, (@csum := @csum + diff) as balance
        FROM {mcapi_transactions_index}
        WHERE wallet_id = :wallet_id AND currcode = :currcode
        ORDER BY created",
      array(
        ':wallet_id' => $account->id(),
        ':currcode' => $currency->id()
      )
    )->fetchAll();
    $history = array();
    //having done the addition, we can chop the beginning off the array
    //process the points into the right format
    //if two transactions happen on the same second, the latter running balance will be shown only
    foreach ($all_balances as $point) {
      if ($point->created > $since) {
        $history[$point->created] = $point->balance;
      }
    }
    return $history;
  }
  /**
   * @see \Drupal\mcapi\TransactionStorageControllerInterface::count()
   */
  public function count($currcode = '', $conditions = array(), $serial = FALSE) {
    $query = $this->database->select('mcapi_transactions_worths', 'w');
    $query->join('mcapi_transactions', 't', 't.xid = w.xid');
    $query->addExpression('count(w.xid)');
    if ($currcode) {
      $query->condition('currcode', $currcode);
    }
    $this->parseConditions($query, $conditions);
    return $query->execute()->fetchField();
  }

  /**
   * @see \Drupal\mcapi\TransactionStorageControllerInterface::volume()
   */
  public function volume($currcode, $conditions = array()) {
    $query = $this->database->select('mcapi_transactions_worths', 'w');
    $query->join('mcapi_transactions', 't', 't.xid = w.xid');
    $query->addExpression('SUM(value)');
    $query->condition('w.currcode', $currcode);
    $this->parseConditions($query, $conditions);
    return $query->execute()->fetchField();
  }
  /**
   * Delete all transactions of a certain currency.
   * @todo inspect and test this!
   *
   * @param string $currcode
   */
  public function currencyDelete($currcode) {
    //remove everything from the worths table, check for orphans and remove the orphans.
    $this->database->delete('mcapi_transaction_worths')->condition('currcode', $currcode)->execute();
    $this->database->query("SELECT xid
        FROM {mcapi_transactions} t
        LEFT JOIN {mcapi_transaction_worths} w ON t.xid = w.xid
        WHERE w.xid IS NULL")->fetchCol();
    $this->database->delete('mcapi_transactions')->condition('xid', $currcode)->execute();
    $this->database->delete('mcapi_transactions_index')->condition('currcode', $currcode)->execute();
    drupal_set_message('currencyDelete has never been tested!', 'warning');
  }

  /**
   * Add an array of conditions to the select query
   *
   * @param AlterableInterface $query
   * @param array $conditions
   */
  private function parseConditions(AlterableInterface $query, array $conditions) {
    foreach ($conditions as $fieldname => $value) {
      $query->conditions($fieldname, $value);
    }
    if (!in_array('state', $conditions) || !is_null($conditions['state'])) {
      $query->conditions('state', '0', '>');
    }
  }
}
