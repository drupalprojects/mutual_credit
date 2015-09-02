<?php

/**
 * @file
 * Contains \Drupal\mcapi\Storage\TransactionIndexStorage.
 *
 * By extending this class the transaction storage can easily read and write to
 * Drupal's own transaction index table.
 *
 * All transaction storage works with individual Drupalish entities and the xid key
 * Only at a higher level do transactions have children and work with serial numbers
 *
 */

namespace Drupal\mcapi\Storage;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\mcapi\Entity\WalletInterface;


abstract class TransactionIndexStorage extends SqlContentEntityStorage implements TransactionStorageInterface {

  /**
   * {@inheritdoc}
   * save 2 rows per worth into the index tables
   * @note $entity cannot have children - this must be called inside foreach($transaction->flatten)
   */
  public function doPostSave(EntityInterface $entity, $update = FALSE) {
    
    //alternatively how about a db_merge? would be quicker
    if ($update) {
      $this->database->delete('mcapi_transactions_index')
        ->condition('serial', $entity->serial->value)
        ->execute();
    }

    foreach ($entity->flatten() as $transaction) {
      $record = $this->mapToStorageRecord($transaction);
      $common = [
        'xid' => $transaction->id(),
        'serial' => $record->serial,
        'state' => $record->state,
        'type' => $record->type,
        'created' => $record->created,
        'changed' => $record->changed,
        'child' => intval((bool)$record->parent),
      ];

      $fields = ['xid', 'serial', 'wallet_id', 'partner_id', 'state', 'curr_id', 'volume', 'incoming', 'outgoing', 'diff', 'type', 'created', 'changed', 'child'];
      $query = $this->database->insert('mcapi_transactions_index')->fields($fields);

      foreach ($transaction->worth->getValue() as $worth) {
        $query->values($common + [
          'wallet_id' => $record->payer,
          'partner_id' => $record->payee,
          'incoming' => 0,
          'outgoing' => $worth['value'],
          'diff' => -$worth['value'],
          'curr_id' => $worth['curr_id'],
          'volume' => $worth['value']
        ]);
        $query->values($common + [
          'wallet_id' => $record->payee,
          'partner_id' => $record->payer,
          'incoming' => $worth['value'],
          'outgoing' => 0,
          'diff' => $worth['value'],
          'curr_id' => $worth['curr_id'],
          'volume' => $worth['value']
        ]);
      }
      $query->execute();
    }
  }
  
  /**
   * {@ineritdoc}
   * @todo test this fires
   */
  protected function doDelete($entities) {
    //first of all we need to get a flat array of all the entities.
    $transactions = [];
    foreach ($entities as $entity) {
      $serials[] = $entity->serial->value;
      foreach($entity->flatten() as $transaction) {
        $transactions[$transaction->id()] = $transaction;
      }
    }
    parent::doDelete($transactions);
    $this->resetCache(array_keys($transactions));
    $this->indexDrop($serials);
    \Drupal::logger('mcapi')->notice(
      'Transactions deleted by user @uid; Serials: @serials',
      [
        '@uid' => \Drupal::currentuser()->id(),
        '@serials' => implode(', ', array_keys($transactions))
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function indexRebuild() {
    $states = $this->countedStates(TRUE);
    db_truncate('mcapi_transactions_index')->execute();
    //don't know how to do this with database API
    $this->database->query("
      INSERT INTO {mcapi_transactions_index} (
        SELECT
          t.xid,
          t.serial,
          t.payer AS wallet_id,
          t.payee AS partner_id,
          t.state,
          t.type,
          t.created,
          t.changed,
          0 AS incoming,
          worth_value AS outgoing,
          - worth_value AS diff,
          worth_value AS volume,
          worth_curr_id,
          t.parent as child
        FROM {mcapi_transaction} t
        RIGHT JOIN {mcapi_transaction__worth} w ON t.xid = w.entity_id
        WHERE state IN ($states)
      )"
    );
    $this->database->query(
      "INSERT INTO {mcapi_transactions_index} (
        SELECT
          t.xid,
          t.serial,
          t.payee AS wallet_id,
          t.payer AS partner_id,
          t.state,
          t.type,
          t.created,
          t.changed,
          worth_value AS incoming,
          0 AS outgoing,
          worth_value AS diff,
          worth_value AS volume,
          worth_curr_id,
          t.parent as child
        FROM {mcapi_transaction} t
        RIGHT JOIN {mcapi_transaction__worth} w ON t.xid = w.entity_id
        WHERE state IN ($states)
      ) "
    );
  }

  /**
   * {@inheritdoc}
   */
  public function indexCheck() {
    if ($this->database->query("SELECT SUM (diff) FROM {mcapi_transactions_index}")->fetchField() +0 == 0) {
      $states = $this->countedStates(TRUE);
      $volume_index = $this->database->query("SELECT sum(incoming) FROM {mcapi_transactions_index}")->fetchField();
      $volume = $this->database->query("SELECT sum(w.worth_value)
        FROM {mcapi_transaction} t
        LEFT JOIN {mcapi_transaction__worth} w ON t.xid = w.entity_id AND t.state IN ($states)"
      )->fetchField();
      if ($volume_index == $volume) return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function indexDrop(array $serials) {
    $this->database->delete('mcapi_transactions_index')
      ->condition('serial', $serials, 'IN')
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function filter(array $conditions = [], $offset = 0, $limit = 0) {
    if (!empty($conditions['payer']) && !empty($conditions['payee'])) {
      throw new Exception('TransactionIndexStorage cannot filter by both payer and payee');
    }

    $query = $this->database->select('mcapi_transactions_index', 'x')
      ->fields('x', array('xid', 'serial'))
      ->orderby('created', 'DESC');
    //in any filter operation we need to need to halve the query because this table
    //has 2 rows for every transaction
    $halve = db_and();
    if (array_key_exists('payer', $conditions)) {
      $halve->condition('uid1', $conditions['payer'])->condition('income', '0');
      unset($condition['payer']);
    }
    else{
      if (array_key_exists('payee', $conditions)) {
        $halve->condition('uid1', $conditions['payee']);
        unset($condition['payee']);
      }
      $halve->condition('expenditure', '0');
    }
    $query->condition($halve);//this ensures we only return one row coz there are 2 foreach transaction

    $this->parseIndexConditions($query, $conditions);

    if ($limit) {
      //assume that nobody would ask for unlimited offset results
      $query->range($offset, $limit);
    }
    return $query->execute()->fetchAllKeyed();
  }

  /**
   * {@inheritdoc}
   */
  public function summaryData($wallet_id, array $conditions = []) {
    $query = $this->database->select('mcapi_transactions_index', 'i')
      ->fields('i', ['curr_id']);
    $this->parseIndexConditions($query, $conditions);
    $query->addExpression('COUNT(DISTINCT i.serial)', 'trades');
    $query->addExpression('SUM(i.incoming)', 'gross_in');
    $query->addExpression('SUM(i.outgoing)', 'gross_out');
    $query->addExpression('SUM(i.diff)', 'balance');
    $query->addExpression('SUM(i.volume)', 'volume');
    $query->addExpression('COUNT(DISTINCT i.partner_id)', 'partners');
    $query->condition('i.wallet_id', $wallet_id)
      ->groupby('curr_id');
    return $query->execute()->fetchAllAssoc('curr_id', \PDO::FETCH_ASSOC);
  }

  /**
   * {@inheritdoc}
   */
  public function balances($curr_id) {
    $query = $this->database->select('mcapi_transactions_index', 'i')
      ->fields('i', ['wallet_id']);
    $query->addExpression('SUM(i.diff)', 'balance');
    $query->condition('i.curr_id', $curr_id)
      ->groupby('curr_id');
    return $query->execute()->fetchAllKeyed();
  }

  /**
   * {@inheritdoc}
   */
  public function timesBalances(WalletInterface $wallet, $curr_id, $since = 0) {
    
    $history = [$wallet->created->value => 0];
    //this is a way to add up the results as we go along
    $this->database->query("SET @csum := 0");
    //have to calculate the whole history by adding up all the diffs
    //It is cheaper to do stuff in mysql
    $wid = $wallet->id();
    $all_balances = $this->database->query(
      "SELECT created, (@csum := @csum + diff) as balance
        FROM {mcapi_transactions_index}
        WHERE wallet_id = $wid AND curr_id = '$curr_id'
        ORDER BY created"
    )->fetchAll();
    //having done the addition, we can chop the beginning off the array
    //if two transactions happen on the same second, the latter running balance will be shown only
    foreach ($all_balances as $point) {
      //@todo find a more efficient way to filter an array where all the keys are < x
      if ($point->created >= $since) {
        $history[$point->created] = $point->balance;
      }
    }
    if (count($history) == 1) return [];
    return $history;
  }

  /**
   * {@inheritdoc}
   */
  public function count($curr_id = '', $conditions = [], $serial = FALSE) {
    $query = $this->database
      ->select('mcapi_transactions_index', 't')
      ->condition('t.incoming', 0);
    $field = $serial ? 'serial' : 'xid';
    $query->addExpression("count($field)");//how do we do this with countquery()
    if ($curr_id) {
      $conditions['curr_id'] = $curr_id;
    }
    $this->parseIndexConditions($query, $conditions);
    return $query->execute()->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  function volume($curr_id, $conditions = []) {
    $query = $this->database->select('mcapi_transactions_index', 't')
      ->condition('incoming', 0);
    $query->addExpression('SUM(t.volume)');
    $query->condition('t.curr_id', $curr_id);
    $this->parseIndexConditions($query, $conditions);
    return intval($query->execute()->fetchField());
  }

  /**
   * Add an array of conditions to the select query
   *
   * @param SelectInterface $query
   * @param array $conditions
   */
  private function parseIndexConditions(SelectInterface $query, array $conditions) {

    if (empty($conditions['state'])) {
      $conditions['state'] = $this->countedStates();
    }
    foreach($conditions as $field => $value) {
      switch($field) {
        case 'xid':
        case 'serial':
        case 'payer':
        case 'payee':
        case 'creator':
        case 'type':
        case 'state':
        case 'curr_id':
            $query->condition($field, $value, is_array($value) ? 'IN' : NULL);
          break;
        case 'involving':
          $value = (array)$value;
          $cond_group = count($value) == 1 ? db_or() : db_and();
          $query->condition($cond_group
            ->condition('wallet_id', $value)
            ->condition('partner_id',$value)
          );
          break;
        case 'since':
          $query->condition('created', $value, '>');
          break;
        case 'until':
          $query->condition('created', $value, '<');
          break;
        case 'value'://synonyms as far as the index table is concerned.
          $query->condition('volume', $value);
          break;
        case 'min'://synonyms as far as the index table is concerned.
          $query->condition('volume', $value, '>=');
          break;
        case 'max'://synonyms as far as the index table is concerned.
          $query->condition('volume', $value, '<=');
          break;
      	default:
          debug('filtering on unknown field: '.$field);
      }
    }
  }

  /**
   * helper function to filter queries by counted states only
   *
   * @param boolean $as_string
   *   return as a string suitable for dropping into a query string.
   *
   * @return string
   *   a comma separated list of state ids, in quote marks
   */
  protected function countedStates($as_string = FALSE) {
    $counted_states = [];
    $counted = array_keys(array_filter(\Drupal::config('mcapi.settings')->get('counted')));
    if ($as_string) {
      foreach ($counted as $state_id) {
        $counted_states[] = "'".$state_id."'";
      }
      $counted = implode(', ', $counted_states);
    }
    return $counted;
  }

  /**
   * {@inheritDoc}
   */
  public function wallets($curr_id, $conditions = []) {
    $query = $this->database->select('mcapi_transactions_index', 'i')
      ->fields('i', ['wallet_id'])
      ->condition('curr_id', $curr_id);
    $this->parseIndexConditions($query, $conditions);
    return $query->distinct()
      ->execute()
      ->fetchCol();
  }

  /**
   * {@inheritDoc}
   */
  public function runningBalance($wid, $curr_id, $until, $sort_field = 'xid') {
    //the running balance depends the order of the transactions. we will assume
    //the order of creation is what's wanted because that corresponds to the 
    //order of the xid. NB it is possible to change the apparent creation date.
    return db_query(
      "SELECT SUM(diff) FROM {mcapi_transactions_index}
        WHERE wallet_id = :wallet_id
        AND curr_id = :curr_id
        AND $sort_field <= :until",
      array(
        ':wallet_id' => $wid,
        ':curr_id' => $curr_id,
        ':until' => $until
      )
    )->fetchField();
  }

  /**
   * for development use only!
   * truncate the transaction index table OR assume that transactions will be deleted individually
   *
   * return integer[]
   *   the serial numbers of all transactions with the given currency
   */
  public function wipeslate($curr_id = NULL) {
    $serials = [];
    //get the serial numbers
    $query = $this->database->select("mcapi_transactions_index", 't')
      ->fields('t', array('serial'));

    if ($curr_id) {
      $query->condition('curr_id', $curr_id);
    }
    $serials = $query->execute()->fetchCol();
    if (!$curr_id) {//that means delete everything
      $this->database->truncate('mcapi_transactions_index')->execute();
    }
    //otherwise index entries will be deleted transaction by transaction
    return $serials;
  }

}
