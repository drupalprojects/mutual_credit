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
  $items['generate-transactions'] = [
    'callback' => 'drush_devel_generate',
    'callback arguments' => [
      'plugin_id' => 'transaction',
    ],
    'description' => 'Create transactions.',
    'arguments' => [
      'num' => 'Number of transactions to generate.',
    ],
    'options' => [
      'kill' => 'Delete all transactions before generating new ones.',
      'type' => 'Specify the transaction type - may affect signatures)',
    ],
    'drupal dependencies' => [
      'devel-generate',
    ],
  ];
  $items['mcapi-wipeslate'] = [
    'callback' => 'drush_mcapi_wipeslate',
    'description' => "CAUTION: Delete all transaction entities from Drupal's database. Will leave values hanging if other entities use the worth field",
    'arguments' => [
      'curr_id' => 'The machine name of the currency',
    ],
    'drupal dependencies' => [
    // Not a real dependency but should reduce the chance of accidents!
      'devel',
    ],
  ];
  return $items;
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
