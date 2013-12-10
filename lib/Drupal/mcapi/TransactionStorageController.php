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

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::attachLoad().
   */
  function attachLoad(&$queried_entities, $load_revision = FALSE) {
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

    parent::attachLoad($queried_entities, $load_revision);
  }

  /*
   * overrides of this delete function may choose to ignore the delete mode
   * override the entity delete controller interface
   * so the delete mode is an optional second argument
   */
  public function delete(array $entities) {
    $delete_state = func_get_arg(2);
    if (is_null($delete_state)) {
      $delete_state = \Drupal::config(mcapi.misc)->get('delete_mode');
    }
    //TODO
    //how do we handle the attached fields?
    switch($delete_state) {
      case MCAPI_UNDO_STATE_REVERSE:
        //add reverse transactions to the children and change the state.
        foreach (array_merge(array($this), $this->children) as $transaction) {
          $reversed = clone $this;
          $reversed->payer = $this->payee;
          $reversed->payee = $this->payer;
          $reversed->type = 'reversal';
          unset($reversed->created, $reversed->xid);
          $reversed->description = t('Reversal of: @label', array('@label' => $entity['label callback']($transaction)));
          $reverseds[] = $reversed;
        }
        $this->children = array_merge($this->children, $reversed);
        //running on..
      case MCAPI_UNDO_STATE_ERASE:
        $old_state = $this->state;
        $this->state = $delete_state;
        try{
          //notice we don't validate here
          $this->save();
          drupal_set_message('update hook needed in TransactionStorageController->delete()');
        }
        catch (Exception $e){
          drupal_set_message(t('Failed to undo transaction: @message', array('@message' => $e->getMessage())));
        }
        break;
      case MCAPI_UNDO_STATE_DELETE:
        $this->database->delete('mcapi_transactions_worths')
          ->condition('xid', $transaction->id())
          ->execute();
          //and the index table
        $this->database->delete('mcapi_transactions_index')
          ->condition('xid', $transaction->id())
          ->execute();
    }
    //TODO
    //does this belong in the entitystorage controller?
    module_invoke_all('transaction_undo', $this->serial->value);
    //once even one transaction has been deleted, the undo_mode cannot be changed
    \Drupal::config('mcapi.misc')->set('change_undo_mode', FALSE);
  }


  /**
   * {@inheritdoc}
   */
  public function saveWorths(TransactionInterface $transaction) {
    $this->database->delete('mcapi_transactions_worths')
      ->condition('xid', $transaction->id())
      ->execute();
    $query = $this->database->insert('mcapi_transactions_worths')
      ->fields(array('xid', 'currcode', 'value'));
    foreach ($transaction->worths[0] as $currcode => $worth) {
      if (!$worth->value) {
        continue;
      };
      $query->values(array(
        'xid' => $transaction->id(),
        'currcode' => $currcode,
        'value' => $worth->value,
      ));
    }
    $query->execute();

    //fire hooks -
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
      ->fields(array('xid', 'uid1', 'uid2', 'currcode', 'volume', 'incoming', 'outgoing', 'diff', 'type', 'created'));
    foreach ($transaction->worths[0] as $currcode => $worth) {
      $query->values(array(
        'xid' => $transaction->id(),
        'uid1' => $transaction->payer->value,
        'uid2' => $transaction->payee->value,
        'currcode' => $currcode,
        'volume' => $worth->value+0,
        'incoming' => 0,
        'outgoing' => $worth->value+0,
        'diff' => -$worth->value+0,
        'type' => $transaction->type->value,
        'created' => $transaction->created->value
      ));
      $query->values(array(
        'xid' => $transaction->id(),
        'uid1' => $transaction->payee->value,
        'uid2' => $transaction->payer->value,
        'currcode' => $currcode,
        'volume' => $worth->value+0,
        'incoming' => $worth->value+0,
        'outgoing' => 0,
        'diff' => $worth->value+0,
        'type' => $transaction->type->value,
        'created' => $transaction->created->value
      ));
    }
    $query->execute();
  }

  public function indexRebuild() {
    db_truncate('mcapi_transactions_index')->execute();
    db_query("INSERT INTO {mcapi_transactions_index} (SELECT
        t.xid,
        t.payer AS uid1,
        t.payee AS uid2,
        t.state,
        t.type,
        t.created,
        w.currcode,
        0 AS incoming,
        w.value AS outgoing,
        - w.value AS diff,
        w.value AS volume
      FROM {mcapi_transactions} t
      RIGHT JOIN {mcapi_transactions_worths} w ON t.xid = w.xid
      WHERE state > 0) "
    );
    db_query("INSERT INTO {mcapi_transactions_index} (SELECT
        t.xid,
        t.payee AS uid1,
        t.payer AS uid2,
        t.state,
        t.type,
        t.created,
        w.currcode,
        w.value AS incoming,
        0 AS outgoing,
        w.value AS diff,
        w.value AS volume
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
      if ($volume_index == $volume) return TRUE;
    }
    return FALSE;
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
    extract($conditions);
    $query = db_select('mcapi_transactions', 'x')
      ->fields('x', array('xid', 'serial'))
      ->orderby('created', 'DESC');

    if ($limit) {
      $query->range($offset, $limit);
    }
    if (isset($serial)) {
      $query->condition('serial', (array)$serial);
    }
    if (isset($state)) {
      $query->condition('state', (array)$state);
    }
    if (isset($payer)) {
      $query->condition('payer', (array)$payer);
    }
    if (isset($payee)) {
      $query->condition('payee', (array)$payee);
    }
    if (isset($creator)) {
      $query->condition('creator', (array)$creator);
    }
    if (isset($type)) {
      $query->condition('type', (array)$type);
    }
    if (isset($involving)) {
      $query->condition(db_or()
        ->condition('payer', (array)$involving)
        ->condition('payee', (array)$involving)
      );
    }
    if (isset($from)) {
      $query->condition('created', $from, '>');
    }
    if (isset($to)) {
      $query->condition('created', $to,  '<');
    }

    if (isset($currcode) || isset($quantity)) {
      $query->join('mcapi_transactions_worths', 'w', 'x.xid = w.xid');
      if (isset($currcode)) {
        $query->condition('currcode', $currcode);
      }
      if (isset($quantity)) {
        $query->condition('quantity', $quantity);
      }
    }
    return $query->execute()->fetchAllKeyed();
  }

  /*
   * get some stats by adding up the transactions for a given user
  * this is currently used for the limits module and for the views handler per-row
  * caching running balances is innappropriate because they would all need recalculating any time a transaction changed state
  * Because this uses the index table, it knows nothing of transactions with state <  1
  */
  public function summaryData(AccountInterface $account, CurrencyInterface $currency, array $filters) {
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
      $serials = transaction_filter(array('payer' => $main, 'payee' => $main));
      //this is usually a small number, but strictly speaking should be done in a batch.
      foreach (array_unique($serials) as $serial) {
        transaction_undo($serial, MCAPI_UNDO_STATE_DELETE);
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
