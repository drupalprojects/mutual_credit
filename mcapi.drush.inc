<?php

/**
 * @file
 * Drush commands for mcapi module.
 */

use Drupal\mcapi\Entity\Transaction;

/**
 * Implementation of hook_drush_command().
 *
 * @return
 *   An associative array describing your command(s).
 */

/**
 * Implements hook_drush_command().
 */
function mcapi_drush_command() {
  $items_NOT_USED['devel-generate-transactions'] = [
    'description' => 'Create transactions.',
    'arguments' => [
      'num' => 'Number of transactions to generate.',
    ],
    'required-arguments' => 1,
    'options' => [
      'kill' => 'Delete all transactions before generating new ones.',
      'type' => 'Specify the transaction type (may affect signatures)',
    ],
    'drush dependencies' => [
      'devel-generate',
    ],
  ];
  $items['mcapi-wipeslate'] = [
    'description' => "CAUTION: Delete all transaction entities from Drupal's database. Will leave values hanging if other entities use the worth field",
    'arguments' => [
      'curr_id' => 'The machine name of the currency',
    ]
  ];
  return $items;
}

function drush_devel_generate_transactions() {

}

/**
 * Delete all transactions of a certain currency.
 *
 * @param string $curr_id
 *   A currency ID.
 *
 * @todo make this work for every entityType with Worth field storage
 */
function drush_mcapi_wipeslate($curr_id) {
  $db = \Drupal::database();
  $db->delete('mcapi_transactions_index')->condition('curr_id', $curr_id)->execute();
  $db->delete('mcapi_transaction__worth')->condition('worth_curr_id', $curr_id)->execute();
  $q = $db->select('mcapi_transaction', 't')->fields('t', ['xid']);
  $q->leftjoin('mcapi_transaction__worth', 'w', 'w.entity_id = t.xid');
  $q->condition('w.entity_id', NULL, 'IS');
  $xids = $q->execute()->fetchCol();
  // Chunk it to save memory.
  foreach (array_chunk($xids, 100) as $chunk) {
    foreach (Transaction::loadMultiple($chunk) as $t) {
      $t->delete();
    }
  }
}
