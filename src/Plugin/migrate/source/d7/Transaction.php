<?php

namespace Drupal\mcapi\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;


/**
 * Drupal 7 transactions source from database.
 *
 * @MigrateSource(
 *   id = "d7_mcapi_transaction",
 *   source_provider = "mcapi"
 * )
 */
class Transaction extends FieldableEntity {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $description_field_name = $this->variableGet('transaction_description_field', 'transaction_description');
    $query = $this->select('mcapi_transactions', 't')
      ->fields('t', ['xid', 'serial', 'payer', 'payee', 'type', 'state', 'creator', 'created']);
    $table_name = 'field_data_' . $description_field_name;
    $field_name = $description_field_name . '_value';
    $query->join($table_name, 'd', "t.xid = d.entity_id");
    $query->addField('d', $field_name, 'description');
    return $query;
  }

  /**
   * {@inheritdoc}
   *
   * @todo I expect much of this will be handled eventually in parent function
   */
  public function prepareRow(Row $row) {
    // Change the entity type and bundle names from d7
    $row->setSourceProperty('entity_type', 'mcapi_transaction');
    $row->setSourceProperty('bundle', 'mcapi_transaction');

    // Get Field API field values (using the d7 entity and bundle names)
    $fields = $this->getFields('transaction', 'transaction');
    foreach (array_keys($fields) as $field) {
      $row->setSourceProperty(
        $field,
        $this->getFieldValues(
          'transaction',
          $field,
          $row->getSourceProperty('xid'),
          $row->getSourceProperty('vid')
        )
      );
    }

    // Get Field API field values.
    foreach (array_keys($this->getFields('transaction')) as $field) {
      $row->setSourceProperty($field, $this->getFieldValues('transaction', $field, $row->getSourceProperty('xid')));
    }
    return parent::prepareRow($row); // This runs hook_migrate_prepare_row
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
      'creator' => $this->t('Created by user id'),
      'created' => $this->t('Created timestamp'),
    ];
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
