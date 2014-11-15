<?php

/**
 * @file
 * Contains \Drupal\mcapi\Storage\WalletStorageSchema.
 */

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

    $schema['mcapi_wallet'] += array(
      'foreign keys' => array(
        'mcapi_transactions_payee' => array(
          'table' => 'mcapi_transaction',
          'columns' => array('wid' => 'payee'),
        ),
        'mcapi_transactions_payer' => array(
          'table' => 'mcapi_transaction',
          'columns' => array('wid' => 'payer'),
        ),
      ),
    );
    $schema['mcapi_wallets_access'] = array(
      'description' => "Access settings for wallet's operations",
      'fields' => array(
        'wid' => array(
          'description' => 'the unique wallet ID',
          'type' => 'int',
          'size' => 'normal',
          'not null' => TRUE,
        ),
        'operation' => array(
          'description' => 'One of list, summary, payin, payout, edit',
          'type' => 'varchar',
          'length' => '8',
        ),
        'uid' => array(
          'description' => 'A permitted user id',
          'type' => 'int',
          'length' => '8',
        )
      ),
      'unique keys' => array(
        'walletUserPerm' => array('wid', 'operation', 'uid'),
      ),
    );
    
    //this belongs with the wallet entity not the exchange entity because
    //the read/write funcations are activated by wallet hooks, not exchange hooks
    $schema['mcapi_wallet_exchanges_index'] = array(
      'description' => "Index to look up wallets parents' exchanges",
      'fields' => array(
        'wid' => array(
          'description' => 'the wallet ID',
          'type' => 'int',
          'size' => 'normal',
          'not null' => TRUE,
        ),
        'exid' => array(
          'description' => 'the exchange ID',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        )
      ),
      'unique keys' => array(
        'wid_exid' => array('wid', 'exid'),
      )
    );
    return $schema;
  }

}
