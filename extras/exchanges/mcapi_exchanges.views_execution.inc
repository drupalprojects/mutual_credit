<?php


use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\mcapi\Exchanges;

/**
 * implements hook_views_query_alter().
 * Implement per-transaction access control on the basis of exchange membership
 *
 * @param Drupal\views\ViewExecutable $view
 * @param Drupal\views\Plugin\views\query\QueryPluginBase $query
 *
 */
function mcapi_exchanges_views_query_alter(ViewExecutable $view, QueryPluginBase $query) {
  list($base, $wallet_fields) = mcapi_views_query_alter_setup($view);
  if (!$base return;
  $exchange_ids = Exchanges::in();
  $details_field = $wallet_table.'.details';
  foreach ($wallet_fields as $db_field_name) {
    //TODO delete this expression
    $exchange_join = array(
      'table' => 'mcapi_wallet_exchanges_index',
      'field' => 'wid',
      'left_table' => $base,
      'left_field' => $db_field_name,
      'adjusted' => TRUE,
      'type' => 'LEFT',
      'extra' => array(
        array(
          'field' => 'exid',
          'value' => $exchange_ids
        ),
      )
    );
    $exchange_join = array(
      'table' => 'og_membership',
      'field' => 'etid',
      'left_table' => $base,
      'left_field' => $db_field_name,
      'adjusted' => TRUE,
      'type' => 'LEFT',
      'extra' => array(
        array(
          'field' => 'exid',
          'value' => $exchange_ids
        ),
      )
    );
    $wallet_exchanges_alias = 'mcapi_wallet_exchanges_'.$db_field_name;
    $query->addRelationship(
      $wallet_exchanges_alias,
      Drupal\views\Views::pluginManager('join')->createInstance('standard', $exchange_join),
      $base
    );
    //the following was wrenched out of the mcapi_views_query_alter()
    //it ensured that wallet view access control was respected between exchanges
    $wallet_operations = db_or();
    //or if we are logged in
    if ($uid = \Drupal::CurrentUser()->id()) {
      $wallet_operations->condition($details_field, WALLET_ACCESS_AUTH);
      //or if the current user is in the same exchange
      $wallet_operations->condition(
        db_and()
        ->condition($details_field, WALLET_ACCESS_EXCHANGE)
        ->condition($wallet_exchanges_alias.'.wid', 0, '>')
      );
    }
    $query->addWhere('both_wallets', $wallet_operations);
    
    
  }
}

/** DELETE THIS
LEFT JOIN {mcapi_wallet_exchanges_index} mcapi_wallet_exchanges_1 //join payee wallet to exchanges table
  ON mcapi_transactions_index.partner_id = mcapi_wallet_exchanges_1.wid
    AND mcapi_wallet_exchanges_1.exid IN ( '1', '2', '3', '4', '5' )//the current user is in these exchanges
LEFT JOIN mcapi_wallet_exchanges_index mcapi_wallet_exchanges //join payer wallet to exchanges table
  ON mcapi_transactions_index.wallet_id = mcapi_wallet_exchanges.wid
    AND mcapi_wallet_exchanges.exid IN ( '1', '2', '3', '4', '5' ) //the current user is in these exchanges

 */
