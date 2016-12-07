<?php

namespace Drupal\mcapi\Storage;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Entity\EntityInterface;

/**
 * Base transaction storage controller for the transaction entity.
 *
 * Stores everything in a table which is designed to work with views.
 */
abstract class TransactionIndexStorage extends SqlContentEntityStorage implements TransactionStorageInterface {

  /**
   * {@inheritdoc}
   *
   * Save 2 rows per worth into the index tables.
   *
   * @note $entity cannot have children - this must be passed a transaction from a flattened array
   * @note this MUST be overridden
   */
  public function doSave($id, EntityInterface $entity) {
    $return = parent::doSave($id, $entity);

    // Alternatively how about a db_merge? would be quicker.
    if (!$entity->isNew()) {
      $this->database->delete('mcapi_transactions_index')
        ->condition('serial', $entity->serial->value)
        ->execute();
    }

    $record = $this->mapToStorageRecord($entity);
    $common = [
      'xid' => $record->xid,
      'serial' => $record->serial,
      'state' => $record->state,
      'type' => $record->type,
      'created' => $record->created,
      'changed' => $record->changed,
      'child' => intval((bool) $record->parent),
    ];

    $fields = [
      'xid',
      'serial',
      'wallet_id',
      'partner_id',
      'state',
      'curr_id',
      'volume',
      'incoming',
      'outgoing',
      'diff',
      'type',
      'created',
      'changed',
      'child',
    ];
    $query = $this->database->insert('mcapi_transactions_index')->fields($fields);

    foreach ($entity->worth->getValue() as $worth) {
      $query->values($common + [
        'wallet_id' => $record->payer,
        'partner_id' => $record->payee,
        'incoming' => 0,
        'outgoing' => $worth['value'],
        'diff' => -$worth['value'],
        'curr_id' => $worth['curr_id'],
        'volume' => $worth['value'],
      ]);
      $query->values($common + [
        'wallet_id' => $record->payee,
        'partner_id' => $record->payer,
        'incoming' => $worth['value'],
        'outgoing' => 0,
        'diff' => $worth['value'],
        'curr_id' => $worth['curr_id'],
        'volume' => $worth['value'],
      ]);
    }
    $query->execute();
    return $return;
  }

  /**
   * {@inheritdoc}
   *
   * @todo test that this fires.
   */
  protected function doDelete($entities) {
    // First of all we need to get a flat array of all the entities.
    $transactions = [];
    foreach ($entities as $entity) {
      $serials[] = $entity->serial->value;
      foreach ($entity->flatten() as $transaction) {
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
        '@serials' => implode(', ', array_keys($transactions)),
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function indexRebuild() {
    $states = $this->countedStates(TRUE);
    db_truncate('mcapi_transactions_index')->execute();
    // don't know how to do this with database API.
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
          if (t.parent, 1, 0) as child
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
          if (t.parent, 1, 0) as child
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
    $q = $this->database->select('mcapi_transactions_index');
    $q->addExpression('SUM(diff)');
    // If the sum of everything is zero.
    if ($q->execute()->fetchfield() + 0 != 0) {
      return FALSE;
    }
    $states = $this->countedStates();
    $q = $this->database->select('mcapi_transactions_index', 'i');
    $q->addExpression('SUM(incoming)');
    $q->condition('i.state', $states, 'IN');
    $volume_index = $q->execute()->fetchField();

    $q = $this->database->select('mcapi_transaction', 't');
    $q->join('mcapi_transaction__worth', 'w', 't.xid = w.entity_id');
    $q->addExpression('SUM(w.worth_value)');
    $q->condition('t.state', $states, 'IN');
    $volume = $q->execute()->fetchField();
    return $volume_index == $volume;
  }

  /**
   * {@inheritdoc}
   */
  public function indexDrop(array $serials) {
    if ($serials) {
      $this->database->delete('mcapi_transactions_index')
        ->condition('serial', $serials, 'IN')
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function walletSummary($curr_id, $wallet_id, array $conditions = []) {
    $conditions['wallet_id'] = $wallet_id;
    $query = $this->getMcapiIndexQuery($curr_id, $conditions);
    $query->addExpression('COUNT(DISTINCT i.serial)', 'trades');
    $query->addExpression('COALESCE(SUM(i.incoming), 0)', 'gross_in');
    $query->addExpression('COALESCE(SUM(i.outgoing), 0)', 'gross_out');
    $query->addExpression('COALESCE(SUM(i.diff), 0)', 'balance');
    $query->addExpression('COALESCE(SUM(i.volume), 0)', 'volume');
    $query->addExpression('COUNT(DISTINCT i.partner_id)', 'partners');
    return $query->execute()->fetch(\PDO::FETCH_ASSOC);
  }

  /**
   * {@inheritdoc}
   */
  public function historyOfWallet($wid, $curr_id, $since = 0) {
    // This is a way to add up the results as we go along.
    $this->database->query("SET @csum := 0");
    // Have to calculate the whole history by adding up all the diffs
    // It is cheaper to do stuff in mysql.
    $query = $this->database->select('mcapi_transactions_index', 'i')->fields('i', ['created']);
    $query->addExpression('(@csum := @csum + diff)', 'balance');
    $all_balances = $query->condition('wallet_id', $wid)
      ->condition('curr_id', $curr_id)
      ->orderby('created', 'ASC')
      ->execute()
      // If two transactions happen on the same second, the latter running
      // balance will be shown only.
      ->fetchAllKeyed();
    $pos = 0;
    // Having done the addition, we can chop the beginning off the array.
    if ($since) {
      // We know they keys are in chronological order.
      foreach (array_keys($all_balances) as $pos => $created) {
        if ($created > $since) {
          // Now the $pos is set.
          break;
        }
      }
      // Cut the beginning off
      // to the end.
      $all_balances = array_slice($all_balances, $pos);
    }
    return $all_balances;
  }

  /**
   * Add an array of conditions to the select query.
   *
   * @param string $curr_id
   *   The ID of a currency.
   * @param array $conditions
   *   Conditions keyed by property name suitable for getMcapiIndexQuery(): xid,
   *    serial, partner_id, wallet_id, creator, type, state, involving, since,
   *    until, value, min, max, curr_id
   *
   * @note be aware that unless this query may return double the expected results, unless filtered appropriately
   */
  private function getMcapiIndexQuery($curr_id, array $conditions = []) {
    if (!$curr_id) {
      throw new \Exception('Currency not specified');
    }
    $query = $this->database->select('mcapi_transactions_index', 'i')->condition('curr_id', $curr_id);
    if (empty($conditions['state'])) {
      $conditions['state'] = $this->countedStates();
    }
    foreach ($conditions as $field => $value) {
      switch ($field) {
        // Case 'payer': these can't work
        // case 'payee':
        case 'xid':
        case 'serial':
        case 'partner_id':
        case 'wallet_id':
        case 'curr_id':
        case 'creator':
        case 'type':
        case 'state':
          // @todo is this [] syntax working? I think not
          $query->condition($field, (array) $value, 'IN');
          break;

        case 'involving':
          $value = (array) $value;
          $cond_group = count($value) == 1 ? db_or() : db_and();
          $query->condition($cond_group
            ->condition('wallet_id', $value)
            ->condition('partner_id', $value)
          );
          break;

        case 'since':
          $query->condition('created', $value, '>');
          break;

        case 'until':
          $query->condition('created', $value, '<');
          break;

        // Synonyms as far as the index table is concerned.
        case 'value':
          $query->condition('volume', $value);
          break;

        // Synonyms as far as the index table is concerned.
        case 'min':
          $query->condition('volume', $value, '>=');
          break;

        // Synonyms as far as the index table is concerned.
        case 'max':
          $query->condition('volume', $value, '<=');
          break;

        default:
          //debug('Filtering on unknown field: ' . $field);
      }
    }
    return $query;
  }

  /**
   * Helper function to filter queries by counted states only.
   *
   * @param bool $as_string
   *   Return as a string suitable for dropping into a query string.
   *
   * @return string
   *   A comma separated list of state ids, in quote marks.
   */
  protected function countedStates($as_string = FALSE) {
    $counted_states = [];
    $counted = array_keys(array_filter(\Drupal::config('mcapi.settings')->get('counted')));
    if ($as_string) {
      foreach ($counted as $state_id) {
        $counted_states[] = "'" . $state_id . "'";
      }
      $counted = implode(', ', $counted_states);
    }
    return $counted;
  }

  /**
   * {@inheritdoc}
   */
  public function wallets($curr_id, $conditions = []) {
    return $this->getMcapiIndexQuery($curr_id, $conditions)
      ->fields('i', ['wallet_id'])
      ->distinct()
      ->execute()
      ->fetchCol();
  }

  /**
   * {@inheritdoc}
   *
   * @note The running balance views field isn't used in any default views.
   */
  public function runningBalance($wid, $curr_id, $until, $sort_field = 'xid') {
    // The running balance depends the order of the transactions. we will assume
    // the order of creation is what's wanted because that corresponds to the
    // order of the xid. NB it is possible to change the apparent creation date.
    $query = $this->database->select('mcapi_transactions_index');
    $query->addExpression('SUM(diff)', 'balance');
    return $query->condition('wallet_id', $wid)
      ->condition('curr_id', $curr_id)
      ->condition($sort_field, $until, '<=')
      ->execute()
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryServiceName() {
    return 'mcapi.query.sql';
  }

  /**
   * {@inheritdoc}
   *
   * Overriding this because transaction entityQuery returns xids and serials.
   */
  public function loadByProperties(array $values = array()) {
    // Build a query to fetch the entity IDs.
    $entity_query = $this->getQuery();
    $this->buildPropertyQuery($entity_query, $values);
    $result = $entity_query->execute();
    return $result ? $this->loadMultiple(array_keys($result)) : array();
  }

  /**
   * {@inheritdoc}
   */
  public function ledgerStateQuery($curr_id, array $conditions) {
    $q = $this->getMcapiIndexQuery($curr_id, $conditions);
    $q->addExpression('SUM(diff)', 'balance');
    $q->addExpression('SUM(volume)/2', 'volume');
    $q->addExpression('SUM(incoming)', 'income');
    $q->addExpression('SUM(outgoing)', 'expenditure');
    $q->addExpression('COUNT(serial)', 'trades');
    $q->addExpression('COUNT (DISTINCT partner_id)', 'partners');
    return $q;
  }

  /**
   * {@inheritdoc}
   */
  public function ledgerStateByWallet($curr_id, array $conditions) {
    $q = $this->ledgerStateQuery($curr_id, $conditions);
    $q->addExpression('wallet_id', 'wid');
    $q->groupby('wallet_id');
    return $q->execute()->fetchAll();
  }

  /**
   * {@inheritdoc}
   */
  public function historyPeriodic($curr_id, $period, $conditions) {
    $q = $this->getMcapiIndexQuery($curr_id, $conditions);
    $q->addExpression('COUNT(DISTINCT xid)', 'trades');
    $q->addExpression('SUM(incoming)', 'volumes');
    $q->addExpression('COUNT(DISTINCT wallet_id)', 'wallets');
    $q->condition('incoming', 0, '>=');
    switch ($period) {
      case 'Day':
        $q->addExpression('DATE(FROM_UNIXTIME(created))', 'date');
        $q->groupBy('DATE(FROM_UNIXTIME(created))');
        break;

      case 'Week':
        $q->addExpression('WEEK(FROM_UNIXTIME(created))', 'weeknum');
        $q->addExpression('MIN(YEAR(FROM_UNIXTIME(created)))', 'year');
        $q->groupBy('WEEK(FROM_UNIXTIME(created))');
        break;

      case 'Month':
        $q->addExpression('MONTH(FROM_UNIXTIME(created))', 'month');
        $q->addExpression('MIN(YEAR(FROM_UNIXTIME(created)))', 'year');
        $q->groupBy('MONTH(FROM_UNIXTIME(created))');
        break;

      case 'Year':
        $q->addExpression('YEAR(FROM_UNIXTIME(created))', 'year');
        $q->groupBy('YEAR(FROM_UNIXTIME(created))');
        break;
    }
    $dates = $trades = $volumes = $wallets = [];
    foreach ($q->execute()->fetchAll() as $row) {
      switch ($period) {
        case 'Day':
          $dates[] = strtotime($row->date);
          break;

        case 'Week':
          // The last second of the week.
          $dates[] = strtotime($row->year . 'W' . str_pad($row->weeknum + 1, 2, 0, STR_PAD_LEFT)) - 1;
          break;

        case 'Month':
          // The last second of the month.
          $dates[] = strtotime($row->year . '-' . ($row->month + 1)) - 1;
          break;

        case 'Year':
          $dates[] = strtotime($row->year + 1) - 1;
      }
      $trades[] = $row->trades;
      $volumes[] = $row->volumes;
      $wallets[] = $row->wallets;
    }
    return [$dates, $trades, $volumes, $wallets];
  }

  /**
   * {@inheritdoc}
   */
  public function currenciesUsed($wid) {
    return $this->database
      ->select('mcapi_transactions_index', 'i')
      ->fields('i', ['curr_id'])
      ->distinct()
      ->condition('wallet_id', $wid)
      ->execute()
      ->fetchCol();
  }

}
