<?php

/**
 * @file
 * Contains \Drupal\mcapi\Storage\TransactionStorageSchema.
 */

namespace Drupal\mcapi\Storage;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\mcapi\Storage\FieldStorageDefinitionInterface;

/**
 * Defines the extra tablesr.
 */
class TransactionStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    return parent::getEntitySchema($entity_type, $reset = FALSE) +
    ['mcapi_transactions_index' => [
      //regardless of whether the TransactionStorage in on-site, the index is kept in Drupal
      //this allows for views integration as long as the storage controller respects the index
      'description' => 'A more queryable way of storing transactions',
      'fields' => [
        'xid' => [
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'description' => 'Primary Key: The xid of the term.'
        ],
        'serial' => [
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'description' => 'The serial number.',
        ],
        'wallet_id' => [
          'type' => 'int',
          'size' => 'normal',
          'not null' => TRUE,
          'description' => 'the main wallet ID'
        ],
        'partner_id' => [
          'type' => 'int',
          'size' => 'normal',
          'not null' => TRUE,
          'description' => 'the partner wallet ID',
        ],
        'state' => [
          'type' => 'varchar',
          'not null' => TRUE,
          'default' => 'done',
          'length' => 15,
          'description' => 'The id of the mcapi_state config entity'
        ],
        'type' => [
          'type' => 'varchar',
          'not null' => TRUE,
          'default' => 'default',
          'length' => 15,
          'description' => 'The id of the mcapi_type config entity'
        ],
        'created' => [
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'The Unix timestamp when the transaction was created.'
        ],
        'changed' => [
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'The Unix timestamp when the transaction was changed.'
        ],
        'incoming' => [
          'type' => 'int',
          'not null' => TRUE,
          'default'=> 0,
          'description' => 'Amount by which the wallet balance increased',
        ],
        'outgoing' => [
          'type' => 'int',
          'not null' => TRUE,
          'default'=> 0,
          'description' => 'Amount by which the wallet balance decreased'
        ],
        'diff' => [
          'type' => 'int',
          'not null' => TRUE,
          'default'=> 0,
          'description' => 'Change in the wallet balance'
        ],
        'volume' => [
          'type' => 'int',
          'not null' => TRUE,
          'default'=> 0,
          'description' => 'Amount of the transaction'
        ],
        'curr_id' => [
          'type' => 'varchar',
          'not null' => TRUE,
          'default' => 'cc',
          'length' => 8,
          'description' => 'The currency ID'
        ],
        'child' => [
          'description' => 'Boolean indicating whether the transation has a parent.',
          'type' => 'int',
          'not null' => FALSE,
          'default' => 0,
          'size' => 'tiny',
        ],
      ],
      'primary key' => ['xid', 'wallet_id', 'curr_id'],
      'indexes' => [
        'wallet_id' => ['wallet_id'],
        'partner_id' => ['partner_id'],
       ]
    ]];
  }

    /**
   * {@inheritdoc}
   * @todo check that the keys are showing up in the data table
   */
  protected function processDataTable(ContentEntityTypeInterface $entity_type, array &$schema) {
    if ($entity_type->id() == 'mcapi_transaction') {
      $schema['mcapi_transaction']['indexes']['parent'] = ['parent'];
      $schema['mcapi_transaction']['foreign keys'] = [
        'payer' => [
          'table' => 'mcapi_wallet',
          'columns' => ['wid' => 'wid'],
        ],
        'payee' => [
          'table' => 'mcapi_wallet',
          'columns' => ['wid' => 'wid'],
        ]
      ];
    }
  }
}

