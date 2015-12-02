mcapi.drush.inc has been included - test generate-transactions<?php

/**
 * Implementation of hook_drush_command().
 * 
 * @return
 *   An associative array describing your command(s).
 */
function mcapi_drush_command() {
  if (\Drupal::moduleHandler()->moduleExists('devel_generate')) {
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
        'type' => 'Specify the transaction type - may affect signatures)'
      ],
      'aliases' => ['genx'],
    ];
  }
  return $items;
}
