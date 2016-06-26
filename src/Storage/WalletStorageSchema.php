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
    $schema = parent::getEntitySchema($entity_type, $reset = FALSE);

    $schema['mcapi_wallet'] += [
      'foreign keys' => [
        'mcapi_transactions_payee' => [
          'table' => 'mcapi_transaction',
          'columns' => ['wid' => 'payee'],
        ],
        'mcapi_transactions_payer' => [
          'table' => 'mcapi_transaction',
          'columns' => ['wid' => 'payer'],
        ],
      ],
    ];
    /*
    $schema['mcapi_wallets_access'] = [
    'description' => "Access settings for wallet's operations",
    'fields' => [
    'wid' => [
    'description' => 'the unique wallet ID',
    'type' => 'int',
    'size' => 'normal',
    'not null' => TRUE,
    ],
    'operation' => [
    'description' => 'One of list, summary, payin, payout, edit',
    'type' => 'varchar',
    'length' => '8',
    ],
    'uid' => [
    'description' => 'A permitted user id',
    'type' => 'int',
    'length' => '8',
    ]
    ],
    'unique keys' => [
    'walletOpUser' => ['wid', 'operation', 'uid'],
    ],
    ];
     *
     */

    return $schema;
  }

}
