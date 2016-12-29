<?php

namespace Drupal\mcapi\Storage;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Defines the extra tables.
 */
class WalletStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $tables = parent::getEntitySchema($entity_type, $reset = FALSE);

    $tables['mcapi_transaction_totals'] = [
      'description' => 'The current state of each wallet',
      'fields' => [
        'wid' => [
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'description' => 'Primary Key: The id of the wallet.',
        ],
        'curr_id' => [
          'type' => 'varchar',
          'not null' => TRUE,
          'default' => 'cc',
          'length' => 8,
          'description' => 'The currency ID',
        ],
        'balance' => [
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'The balance',
        ],
        'volume' => [
          'type' => 'int',
          'size' => 'normal',
          'not null' => TRUE,
          'description' => 'The total transaction volume',
        ],
        'trades' => [
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'Number of transactions the wallet has been involved in',
        ],
        'gross_in' => [
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'Gross income',
        ],
        'gross_out' => [
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'Gross Expenditure',
        ],
        'partners' => [
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'number of trading partners',
        ],
      ],
      'primary key' => ['wid', 'curr_id']
    ];
    return $tables;
  }

}
