<?php

/**
 * @file
 * Contains \Drupal\mcapi\TransactionStorageController.
 * this uses sql for speed rather than the Drupal DbAPI
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\FieldableDatabaseStorageController;
use Drupal\mcapi\Plugin\field\field_type\Worth;

class TransactionStorageController extends FieldableDatabaseStorageController implements TransactionStorageControllerInterface {

/*
 *  TODO GORDON where are the transaction->children saved?
 * each transaction should be simply skipped if $this->entity->errors
 * this is how invalid child transactions don't break everything
 */


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

    // Load all the children
    $result = $this->database->query('SELECT xid FROM {mcapi_transactions} WHERE parent IN (:parents)', array(':parents' => array_keys($queried_entities)));
    foreach ($result as $record) {
      $queried_entities[$record->xid]->children[$record->xid] = NULL;
    }

    parent::postLoad($queried_entities);
  }

  /*
   * in THIS storage controller, 'delete' is more like 'erase', merely changing the transaction state.
   * Would be very easy to make another storage controller where delete either deletes the entity
   * Or even creates another transaction going in the opposite direction
   */
  public function delete(array $transactions) {
    foreach ($transactions as $transaction) {
      $transaction->state = TRANSACTION_STATE_UNDONE;
      try{
        $transaction->save($transaction);
        $this->indexDrop($transaction->serial->value);
        drupal_set_message('update hook needed in TransactionStorageController->delete()?');
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
   * {@inheritdoc}
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

    //TODO fire hooks -
    //transaction_update($op, $transaction, $values);
  }

  /*
   *  write 2 rows to the transaction index table
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
      ->fields(array('xid', 'serial', 'uid1', 'uid2', 'state', 'currcode', 'volume', 'incoming', 'outgoing', 'diff', 'type', 'created', 'child'));

    foreach ($transaction->worths[0] as $worth) {
      $query->values(array(
        'xid' => $transaction->id(),
        'serial' => $transaction->serial->value,
        'uid1' => $transaction->payer->value,
        'uid2' => $transaction->payee->value,
        'state' => $transaction->state->value,
        'currcode' => $worth->currcode,
        'volume' => $worth->value,
        'incoming' => 0,
        'outgoing' => $worth->value,
        'diff' => -$worth->value,
        'type' => $transaction->type->value,
        'created' => $transaction->created->value,
      	'child' => !$transaction->parent->value
      ));
      $query->values(array(
        'xid' => $transaction->id(),
        'serial' => $transaction->serial->value,
        'uid1' => $transaction->payee->value,
        'uid2' => $transaction->payer->value,
        'state' => $transaction->state->value,
        'currcode' => $worth->currcode,
        'volume' => $worth->value,
        'incoming' => $worth->value,
        'outgoing' => 0,
        'diff' => $worth->value,
        'type' => $transaction->type->value,
        'created' => $transaction->created->value,
      	'child' => !$transaction->parent->value
      ));
    }
    $query->execute();
  }

  public function indexRebuild() {
    db_truncate('mcapi_transactions_index')->execute();
    db_query("INSERT INTO {mcapi_transactions_index} (SELECT
        t.xid,
    		t.serial,
        t.payer AS uid1,
        t.payee AS uid2,
        t.state,
        t.type,
        t.created,
        w.currcode,
        0 AS incoming,
        w.value AS outgoing,
        - w.value AS diff,
        w.value AS volume,
    		t.parent as child
      FROM {mcapi_transactions} t
      RIGHT JOIN {mcapi_transactions_worths} w ON t.xid = w.xid
      WHERE state > 0) "
    );
    db_query("INSERT INTO {mcapi_transactions_index} (SELECT
        t.xid,
    		t.serial,
        t.payee AS uid1,
        t.payer AS uid2,
        t.state,
        t.type,
        t.created,
        w.currcode,
        w.value AS incoming,
        0 AS outgoing,
        w.value AS diff,
        w.value AS volume,
    		t.parent as child
      FROM {mcapi_transactions} t
      RIGHT JOIN {mcapi_transactions_worths} w ON t.xid = w.xid
      WHERE state > 0) "
    );
  }
  /*
   * return 1 if the table is integral
   */
  public function indexCheck() {
    if (db_query("SELECT SUM (diff) FROM {mcapi_transactions_index}")->fetchField() +0 == 0) {
      $volume_index = db_query("SELECT sum(incoming) FROM {mcapi_transactions_index}")->fetchField();
      $volume = db_query("SELECT sum(value) FROM {mcapi_transactions} t LEFT JOIN {mcapi_transactions_worths} w ON t.xid = w.xid AND t.state > 0")->fetchField();
      debug("$volume_index == $volume");
      if ($volume_index == $volume) return TRUE;
    }
    return FALSE;
  }

  public function indexDrop($serial) {
    db_delete('mcapi_transactions_index')->condition('serial', $serial)->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function nextSerial(TransactionInterface $transaction) {
    //TODO: I think this needs some form of locking so that we can't get duplicate transactions.
    $transaction->serial->value = $this->database->query("SELECT MAX(serial) FROM {mcapi_transactions}")->fetchField() + 1;
  }


  /*
   * Get a list of xids and serial numbers
   * see transaction.api.php for arguments
   * this would be more useful when views isn't available
   */
  public function filter(array $conditions, $offset, $limit) {
    $query = db_select('mcapi_transactions', 'x')
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
      $query->range($offset, $limit);
    }
    //TODO
    //If there is anything left in $conditions, it must be a fieldAPI field.
    //How to handle that?
    return $query->execute()->fetchAllKeyed();
  }

  /*
   * get some stats by adding up the transactions for a given user
  * this is currently used for the limits module and for the views handler per-row
  * caching running balances is innappropriate because they would all need recalculating any time a transaction changed state
  * Because this uses the index table, it knows nothing of transactions with state <  1
  * //TODO this CurrencyInterface isn't being enforced
  */
  public function summaryData(AccountInterface $account, CurrencyInterface $currency, array $filters) {
    //TODO We need to return 0 instead of null for empty columns
    //then get rid of the last line of this function
    $query = "SELECT
      COUNT(DISTINCT t.serial) as trades,
      SUM(i.incoming) as gross_in,
      SUM(i.outgoing) as gross_out,
      SUM(i.diff) as balance,
      SUM(i.volume) as volume,
      COUNT(DISTINCT i.uid2) as partners
      FROM {mcapi_transactions_index} i
      LEFT JOIN {mcapi_transactions} t ON i.xid = t.xid
      WHERE i.uid1 = :uid1 AND i.currcode = :currcode " . mcapi_parse_conditions($filters);
    $params = array(
      ':uid1' => $account->id(),
      ':currcode' => $currency->id()
    );
    if ($result = db_query($query, $params)->fetchAssoc()) {
      return $result;
    }
    //if there are no transactions for this user
    return array('trades' => 0, 'gross_in' => 0, 'gross_out' => 0, 'balance' => 0, 'volume' => 0, 'partners' => 0);
  }

  //merge the uids in all the subsequent args into the first arg
  public function mergeAccounts($main) {
    $uids = func_get_args();
    $main = array_shift($uids);
    foreach ($uids as $uid) {
      db_update('mcapi_transactions')
      ->fields(array('payer' => $main))
      ->condition('payer', $uid)->execute();
      db_update('mcapi_transactions')
      ->fields(array('payee' => $main))
      ->condition('payee', $uid)->execute();
      //now undo any transactions the account has with itself
      $serials = transaction_filter(array('payer' => $main, 'payee' => $main));
      //this is usually a small number, but strictly speaking should be done in a batch.
      foreach (array_unique($serials) as $serial) {
        mcapi_transaction_load($serial)->delete();
      }
    }
  }

  /*
   * return an array of unixtimes and balances.
   */
  public function timesBalances(AccountInterface $account, CurrencyInterface $currency, $since = 0) {
    //this is a way to add up the results as we go along
    db_query("SET @csum := 0");
    //I wish there was a better way to do this.
    //It is cheaper to do stuff in mysql
    $all_balances = db_query(
      "SELECT created, (@csum := @csum + diff) as balance
        FROM {mcapi_transactions_index}
        WHERE uid1 = :uid1 AND currcode = :currcode
        ORDER BY created",
      array(
        ':uid1' => $account->id(),
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

  public function count($currcode) {
    return db_select('mcapi_transactions_worths', 'w')
    ->fields('w', array('xid'))
    ->condition('currcode', $currcode)
    ->distinct()
    ->execute()
    ->fetchfield();
  }
  public function volume($currcode) {
    return db_query("SELECT SUM(value) FROM {mcapi_transactions_worths}
      WHERE currcode = :currcode", array(':currcode' => $currcode)
    )->fetchField();
  }
}


function mcapi_parse_conditions($conditions) {
  if (empty($conditions)) return '';
  $where = array();
  foreach ($conditions as $condition) {
    if (is_array($condition)) {
      $condition[] = '=';
      list($field, $value, $operator) = $condition;
      if (empty($operator)) $operator = ' = ';
      if (is_array($value)) {
        $value = '('.implode(', ', $value) .')';
        $operator = ' IN ';
      }
      $where[] = " ( t.$field $operator $value ) ";
    }
    else {//the condition is already provided as a string
      $where[] = " $condition ";
    }
  }
  return ' AND '. implode(' AND ', $where);
}
