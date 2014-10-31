<?php

/**
 * @file
 * Contains \Drupal\mcapi\Storage\TransactionStorageSchema.
 */

namespace Drupal\mcapi\Storage;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Defines the extra tablesr.
 */
class TransactionStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset = FALSE);

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

    //regardless of whether the TransactionStorage in on-site, the index is kept in Drupal
    //this allows for views integration as long as the storage controller respects the index
    $schema['mcapi_transactions_index'] = array(
      'description' => 'A more queryable way of storing transactions',
      'fields' => array(
        'serial' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'description' => 'The serial number.',
        ),
        'xid' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'description' => 'Primary Key: The xid of the term.'
        ),
        'wallet_id' => array(
          'type' => 'int',
          'size' => 'normal',
          'not null' => TRUE,
          'description' => 'the main wallet ID'
        ),
        'partner_id' => array(
          'type' => 'int',
          'size' => 'normal',
          'not null' => TRUE,
          'description' => 'the partner wallet ID',
        ),
        'state' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'default' => TRANSACTION_STATE_FINISHED,
          'length' => 15,
          'description' => 'The id of the mcapi_state config entity'
        ),
        'type' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'default' => 'default',
          'length' => 15,
          'description' => 'The id of the mcapi_type config entity'
        ),
        'created' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'The Unix timestamp when the transaction was created.'
        ),
        'changed' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'The Unix timestamp when the transaction was changed.'
        ),
        'exchange' => array(
          'description' => 'The exchange ID.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'incoming' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default'=> 0,
          'description' => 'Amount by which the wallet balance increased',
        ),
        'outgoing' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default'=> 0,
          'description' => 'Amount by which the wallet balance decreased'
        ),
        'diff' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default'=> 0,
          'description' => 'Change in the wallet balance'
        ),
        'volume' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default'=> 0,
          'description' => 'Amount of the transaction'
        ),
        'curr_id' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'default' => 'cc',
          'length' => 8,
          'description' => 'The currency ID'
        ),
        'child' => array(
          'description' => 'Boolean indicating whether the transation has a parent.',
          'type' => 'int',
          'not null' => FALSE,
          'default' => 0,
          'size' => 'tiny',
        ),
      ),
      'primary key' => array('xid', 'wallet_id', 'curr_id'),
      'indexes' => array(
        'wallet_id' => array('wallet_id'),
        'partner_id' => array('partner_id'),
       )
    );
    return $schema;
  }
}
