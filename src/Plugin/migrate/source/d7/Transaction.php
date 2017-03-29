<?php

namespace Drupal\mcapi\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;
use Drupal\migrate\Plugin\MigrationInterface;


/**
 * Drupal 7 transactions source from database.
 *
 * @MigrateSource(
 *   id = "d7_mcapi_transaction",
 *   source_provider = "mcapi"
 * )
 */
class Transaction extends FieldableEntity {//implements MigrationInterface {

  private $description_field_name;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state, EntityManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $entity_manager);
    $this->description_field_name = $this->variableGet('transaction_description_field', 'transaction_description');

  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    //db_query("update hamlets.migrate_map_d7_mcapi_transaction set source_row_status = 1");
    $query = $this->select('mcapi_transactions', 't')
      ->fields('t', ['xid', 'serial', 'payer', 'payee', 'type', 'state', 'creator', 'created']);
    $query->join('field_data_' . $this->description_field_name, 'd', "t.xid = d.entity_id");
    $query->addField('d', $this->description_field_name . '_value', 'description');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Get Field API field values.
//    foreach (array_keys($this->getFields('mcapi_transaction', 'mcapi_transaction')) as $field) {
//      $xid = $row->getSourceProperty('xid');
//      $row->setSourceProperty($field, $this->getFieldValues('mcapi_transaction', $field, $xid));
//    }
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
      'created' => $this->t('Created timestamp'),
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
