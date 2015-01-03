<?php

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\query\QueryPluginBase;

/* see https://api.drupal.org/api/drupal/core!modules!views!views.module/function/views_hook_info/8
    'views_query_substitutions',
    'views_form_substitutions',
    'views_pre_view',
    'views_pre_build',
    'views_post_build',
    'views_pre_execute',
    'views_post_execute',
    'views_pre_render',
    'views_post_render',
    'views_query_alter',
*/

/**
 * implements hook_views_query_alter().
 * Implement per-transaction access control
 *
 * @param Drupal\views\ViewExecutable $view
 * @param Drupal\views\Plugin\views\query\QueryPluginBase $query
 *$ase_b$ase
 */
function mcapi_views_query_alter(ViewExecutable $view, QueryPluginBase $query) {

  list($base, $wallet_fields) = mcapi_views_query_alter_setup($view);
  if (!$base) return;
  $query->setWhereGroup('AND', 'both_wallets');

  foreach ($wallet_fields as $db_field_name) {
    $wallet_join = array(
      'table' => 'mcapi_wallet',
      'field' => 'wid',
      'left_table' => $base,
      'left_field' => $db_field_name,
      'adjusted' => TRUE,
      'type' => 'LEFT',
    );
    $wallet_table = 'mcapi_wallet_'.$db_field_name;
    $details_field = $wallet_table.'.details';
    $query->addRelationship(
      $wallet_table,
      Drupal\views\Views::pluginManager('join')->createInstance('standard', $wallet_join),
      $base
    );

    $users_join = array(
      'table' => 'mcapi_wallets_access',
      'field' => 'wid',
      'left_table' => $base,
      'left_field' => $db_field_name,
      'adjusted' => TRUE,
      'type' => 'LEFT',
      'extra' => array(
        array(
          'field' => 'operation',
          'value' => 'details'
        ),
      )
    );
    $query->addRelationship(
      'mcapi_wallet_access_designated',
      Drupal\views\Views::pluginManager('join')->createInstance('standard', $users_join),
      $base
    );

    $wallet_operations = db_or();

    //if the wallet allows anyone to see
    $wallet_operations->condition($details_field, WALLET_ACCESS_ANY);

    //or if we are logged in
    if ($uid = \Drupal::CurrentUser()->id()) {
      $wallet_operations->condition($details_field, WALLET_ACCESS_AUTH);
      //or if the current user is named
      $wallet_operations->condition(
        db_and()
        ->condition($details_field, WALLET_ACCESS_USERS)
        ->condition('mcapi_wallet_access_designated.uid', $uid)
      );
    }
    $query->addWhere('both_wallets', $wallet_operations);
  }
}

/**
 * helper to alter a transaction view
 * 
 * @param ViewExecutable $view
 * 
 * @return array
 *   the base table name and the names of the two wallet fields in that table
 */
function mcapi_views_query_alter_setup(ViewExecutable $view) {
  $base = $view->storage->get('base_table');
  if (!in_array($base, ['mcapi_transaction', 'mcapi_transactions_index'])){
    return;
  }
  $fields = $base == 'mcapi_transaction' ? 
    ['payer', 'payee'] : 
    ['wallet_id', 'partner_id'];
  return [$base, $fields];
}

/*
 * Here is a typical views query, with a few brackets removed
SELECT *
FROM mcapi_transactions_index
LEFT JOIN mcapi_wallet mcapi_wallet_wallet_id //join to payer wallet
  ON mcapi_transactions_index.wallet_id = mcapi_wallet_wallet_id.wid
LEFT JOIN mcapi_wallets_access mcapi_wallet_access_designated //join payer wallet to access table
  ON mcapi_transactions_index.wallet_id = mcapi_wallet_access_designated.wid
    AND mcapi_wallet_access_designated.operation = 'details'
LEFT JOIN mcapi_wallet mcapi_wallet_partner_id //join to payee wallet
  ON mcapi_transactions_index.partner_id = mcapi_wallet_partner_id.wid
LEFT JOIN mcapi_wallets_access mcapi_wallet_access_designated_1 //join payee wallet to access table
  ON mcapi_transactions_index.partner_id = mcapi_wallet_access_designated_1.wid
    AND mcapi_wallet_access_designated_1.operation = 'details'
WHERE (
  (mcapi_wallet_wallet_id.details = '1')//anon can view payer
  OR
  (mcapi_wallet_wallet_id.details = '2')//authenticated can view payer
  OR
  (mcapi_wallet_wallet_id.details = '3') AND (mcapi_wallet_exchanges.wid > '0')//someone in same exchange can view payer
  OR
  (mcapi_wallet_wallet_id.details = '0') AND (mcapi_wallet_access_designated.uid = '1')//named user can view payer
) AND (
  (mcapi_wallet_partner_id.details = '1')//anon can view payee
  OR
  (mcapi_wallet_partner_id.details = '2')//authenticated can view payee
  OR
  (mcapi_wallet_partner_id.details = '3') AND (mcapi_wallet_exchanges.wid > '0')//someone in same exchange can view payee
  OR
  (mcapi_wallet_partner_id.details = '0') AND (mcapi_wallet_access_designated.uid = '1')//named user can view payee
)
*/

