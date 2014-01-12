<?php


/*
 * implements hook_query_TAG_alter for views on the mcapi_transactions table
* this modifies the query only to show the transactions visible to the user
* according to currency view access settings per transaction state
* this is pretty elaborate, but the access control has to be done in the query
* or views pager won't know how many results there were
*/
function mcapi_query_transaction_view_access_alter(QueryAlterableInterface $query) {
  $query->addJoin('LEFT', 'field_data_worth', 'w', 'w.entity_id = mcapi_transactions.xid');return;
  $states = array_keys(mcapi_get_states(TRUE));
  //what currencies can the current user see?
  $currencies = currencies_load($GLOBALS['user']);
  $or_currcodes = db_or();//where ((currcode = A and (blah)) OR ((currcode = B and (blah))
  foreach ($currencies as $currcode => $currency) {
    //where (currcode = A and (state = x) OR (state = Y) )
    $state_or_state = db_or();//where state = A OR B
    foreach ($states as $state) {
      $callbacks = array_filter($currency->access_view[$state]);
      foreach ($callbacks as $callback) {
        $callback .= '_views';
        $state_and_callback = db_and()->condition('mcapi_transactions.state', $state);
        $callback($query, $state_and_callback, $state);
        $state_or_state->condition($state_and_callback);
      }
    }
    $currcode_and_states = db_and()->condition('w.worth_currcode', $currcode)->condition($state_or_state);
    $or_currcodes->condition($currcode_and_states);
  }
  //to see this working, show the sql query on admin/structure/views/settings/basic
  $query->condition($or_currcodes);
}

//views access control callback
//this needs to happen on any transaction query
//instead of being a views access plugin
function views_plugin_access_user_arg_check() {
  return \Drupal::currentUser()->id() == arg(1) || user_access('manage all transactions');
}

/*
 * implements hook_views_data_alter
 * anything (field) that is joined to mcapi_transactions should be joined to mcapi_index also
 * except worth field, which is copied in the table already
 */
function mcapi_index_views_views_data_alter(&$tables) {

  foreach ($tables as $table => $info) {
    //ignore the fields we know about, this is just intended for fields added to transactions via field API
    if (in_array($table, array('field_data_worth', 'mcapi_signatures'))) continue;
    if (array_key_exists('table', $info) && array_key_exists('join', $info['table']) && array_key_exists('mcapi_transactions', $info['table']['join'])) {
      $tables[$table]['table']['join']['mcapi_index'] = $info['table']['join']['mcapi_transactions'];
      if (!array_key_exists('entity_id', $info)) continue;
      //can't remember what 'moved to' is for
      $fieldname = $info['entity_id']['field']['moved to'][1];
      if (is_array($info[$fieldname]['field']['entity_tables'])) {
        $tables[$table][$fieldname]['field']['entity_tables']['mcapi_index'] = 'transaction';
      }
    }
  }
  //tell the date_views module about this table
  $tables['mcapi_index']['date_argument'] = array(
    'group' => t('Date'),
    'title' => t('Date (!base_table)', array('!base_table' => 'mcapi_index')),
    'help' => t('Filter any Views !base_table date field by a date argument, using any common ISO date/period format (i.e. YYYY, YYYY-MM, YYYY-MM-DD, YYYY-W99, YYYY-MM-DD--P3M, P90D, etc). ', array('!base_table' => 'mcapi_index')),
    'argument' => array(
      'handler' => 'date_views_argument_handler',
      'empty field name' => t('Undated'),
      'is date' => TRUE,
    ),
  );
  // The flexible date filter.
  $tables['mcapi_index']['date_filter'] = array(
    'group' => t('Date'),
    'title' => t('Date (!base_table)', array('!base_table' => 'mcapi_index')),
    'help' => t('Filter any Views !base_table date field.', array('!base_table' => 'mcapi_index')),
    'filter' => array(
      'handler' => 'date_views_filter_handler',
      'empty field name' => t('Undated'),
      'is date' => TRUE,
    ),
  );
}


