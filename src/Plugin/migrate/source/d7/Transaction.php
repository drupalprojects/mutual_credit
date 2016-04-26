<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\migrate\source\d7\Transaction.
 */

namespace Drupal\mcapi\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;

/**
 * Drupal 7 transactions source from database.
 *
 * @MigrateSource(
 *   id = "d7_mcapi_transaction"
 * )
 */
class Transaction extends FieldableEntity {

  /**
   * {@inheritdoc}
   */
  public function query() {
    db_query("update hamlets.migrate_map_d7_transaction set source_row_status = 1");
    $transaction_description_field = $this->variableGet('transaction_description_field', 'transaction_description');
    $query = $this->select('mcapi_transactions', 't')->fields('t');
    $query->join('field_data_'.$transaction_description_field, 'd', "t.xid = d.entity_id");
    $query->addField('d', $transaction_description_field.'_value', 'description');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Get Field API field values.
    foreach (array_keys($this->getFields('mcapi_transaction', 'mcapi_transaction')) as $field) {
      $xid = $row->getSourceProperty('xid');
      $row->setSourceProperty($field, $this->getFieldValues('mcapi_transaction', $field, $xid));
    }
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'xid' => $this->t('Transaction ID'),
      'serial' => $this->t('Serial number'),
      'payer' => $this->t('Payer user'),
      'payee' => $this->t('Payee user'),
      'type' => $this->t('Type'),
      'state' => $this->t('State'),
      'description' => $this->t('Description'),
      'uid' => $this->t('Created by user id'),
      'created' => $this->t('Created timestamp')
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['xid']['type'] = 'integer';
    $ids['xid']['alias'] = 't';
    return $ids;
  }

}
