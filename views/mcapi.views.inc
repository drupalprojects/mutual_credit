<?php
// $Id:

/*
 * Implements views hook_views_data
 */
function mcapi_views_data() {
  $data = array();
  $data['mcapi_transactions']['table'] = array(
    'group'  => t('Transaction'),
    'base' => array(
      'database' => variable_get('mcapi_db_read', 'default'),
      'field' => 'xid',
      'title' => t('Transactions'),
      'help' => t('Records of transactions between users'),
      'access query tag' => 'transaction_access_views',
      'defaults' => array(
        'field' => 'xid',
      ),
    ),
    'entity type' => 1,//so the entity module can expose this table
  );
  $data['mcapi_transactions']['xid'] = array(
    'title' => t('Transaction ID'), // The item it appears as on the UI,
    'field' => array(
      'help' => t('The unique ID of the transaction.'),
      'handler' => 'views_handler_field',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    )
  );
  $data['mcapi_transactions']['payer'] = array(
    'title' => t('Payer'),
    'help' => t('The user who gave currency in the transaction.'),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'users',
      'label' => t('Payer'),
    ),
  );
  $data['mcapi_transactions']['payee'] = array(
    'title' => t('Payee'),
    'help' => t('The user who gave currency in the transaction.'),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'users',
      'label' => t('Payee'),
    ),
  );
  $data['mcapi_transactions']['state'] = array(
    'title' => t('State'),
    'help' => t("Transactions move between states, until they are 'finished'."),
    'field' => array(
      'handler' => 'views_handler_field_mcapi_state',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_mcapi_states',
    ),
  );

  $data['mcapi_transactions']['type'] = array(
    'title' => t('Type'),
    'help' => t('Which form or module which created the transaction'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_mcapi_types',
    ),
  );
  $data['mcapi_transactions']['creator'] = array(
    'title' => t('Creator'),
    'help' => t('The user who first created the transaction.'),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'users',
      'label' => t('Creator'),
    ),
  );
  $data['mcapi_transactions']['created'] = array(
    'title' => t('created'),
    'help' => t("the second the transaction was created"),
    'field' => array(
      'handler' => 'views_handler_field_date',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort_date',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_date',
    ),
    'argument' => array(
      'field' => 'created',
      'handler' => 'views_handler_argument_mcapi_dates_various',
    )
  );
  $data['mcapi_transactions']['modifer'] = array(
    'title' => t('Modifier'),
    'help' => t('The user who last touched the transaction.'),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'users',
      'label' => t('Modifier'),
    ),
  );
  $data['mcapi_transactions']['modified'] = array(
    'title' => t('Corrected'),
    'help' => t("the second the transaction was corrected"),
    'field' => array(
      'handler' => 'views_handler_field_time_interval',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort_date',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_date',
    ),
  );
  $data['mcapi_transactions']['view_transaction'] = array(
    'field' => array(
      'title' => t('Link'),
      'help' => t('Link to the transaction certificate itself.'),
      'handler' => 'views_handler_field_transaction_link',
    ),
  );

  $data['mcapi_transactions']['edit_transaction'] = array(
    'field' => array(
      'title' => t('Edit link'),
      'help' => t('Provide a simple link to edit the transaction.'),
      'handler' => 'views_handler_field_transaction_link_edit',
    ),
  );

  $data['mcapi_transactions']['links'] = array(
    'title' => t('Admin links'),
    'help' => t("Edit and delete links"),
    'field' => array(
      'handler' => 'views_handler_field_mcapi_links',
    )
  );
  return $data;
}

/*
 * implements hook_views_data_alter
 */
function mcapi_views_data_alter(&$data) {
  //this is a powerful way for virtual field balances to be accessible to views based on the user table
  //however it does require a query for each row which calls on a special API function
  $data['users']['current_balance'] = array(
    'title' => t('Transaction statistic'),
    'field' => array(
      'help' => t("The balance, trading volume, or transaction count for each user"),
      'handler' => 'views_handler_field_mcapi_users_totals',
      'click sortable' => TRUE
    )
  );
  //override the default text handler put there for fieldAPI fields, so the filter shows a dropdown
  $data['field_data_worth']['worth_currcode']['filter']['handler'] = 'views_handler_filter_mcapi_currency';
}

/**
 * Implements hook_views_plugins().
 * can't get this working
 */
function mcapi_views_plugins() {
  return array(
    'module' => 'views', // This just tells our themes are elsewhere.
    'row' => array(
      'transaction' => array(
        'title' => t('Transaction'),
        'help' => t('The spacious, decorated, transaction certificate'),
        'handler' => 'views_plugin_row_transaction',
        'base' => array('mcapi_transactions'),//plugin will only be available when this is the base table
        'uses options' => TRUE,
        'type' => 'normal',
      )
    )
  );
}


/*
 * implements hook_form_alter
 * renames the exposed filters on views forms
 */
function mcapi_form_views_exposed_form_alter(&$form, $form_state) {
  $description = t('Comma separated usernames');
  $form['payee']['#description'] = $description;
  $form['payer']['#description'] = $description;
}




//must return a condition objectmcapi_transactions.state
function mcapi_access_unrestricted_views($state){
  $condition = db_and();
  return $condition->condition('mcapi_transactions.state', $state);
}
//must return a condition objectmcapi_transactions.state
function mcapi_access_creator_views($state){
  $condition = db_and();
  return $condition->condition('mcapi_transactions.creator', $GLOBALS['user']->uid);
}
function mcapi_access_authenticated_views($state){
  $condition = db_and();
  $condition->condition('mcapi_transactions.state', $state);
  $condition->condition(0, $GLOBALS['user']->uid, '<>');
  return $condition;
}
function mcapi_access_none_views($state){
  $condition = db_and();
  $condition->condition('mcapi_transactions.state', $state);
  $condition->condition(0, 1);//which is to say, FALSE
  return $condition;
}
function mcapi_access_participants_admin_views($state) {
  $condition = db_and();
  $condition->condition('mcapi_transactions.state', $state);
  $subcondition = db_or();
  $subcondition->condition('mcapi_transactions.payee', $GLOBALS['user']->uid);
  $subcondition->condition('mcapi_transactions.payer', $GLOBALS['user']->uid);
  $subcondition->condition(1, user_access('administer all transactions'));
  $condition->condition($subcondition);
  return $condition;
}
function mcapi_access_admin_views($state) {
  $condition = db_and();
  $condition->condition('mcapi_transactions.state', $state);
  $condition->condition(1, user_access('administer all transactions'));
  return $condition;
}
function mcapi_access_participants_views($state) {
  $condition = db_and();
  $condition->condition('mcapi_transactions.state', $state);
  $subcondition = db_or();
  $subcondition->condition('mcapi_transactions.payee', $GLOBALS['user']->uid);
  $subcondition->condition('mcapi_transactions.payer', $GLOBALS['user']->uid);
  $condition->condition($subcondition);
  return $condition;
}

//this isn't working without the $currency, which the others do not need
function mcapi_access_currency_views($state) {
  $condition = db_and();
  return $condition->condition('mcapi_transactions.state', $state);
}


/*
 * implements hook_query_TAG_alter
 * the saved view adds the tag 'transaction_access'
 * this modifies the query only to show the transactions visible to the user.
 * this is pretty elaborate, but the access control has to be done in the query or views pager won't know how many results there were
 */
function _mcapi_query_transaction_access_views_alter(QueryAlterableInterface &$query) {
  $query->addJoin('LEFT', 'field_data_worth', 'tw', 'tw.entity_id = xid');
  //what currencies can the current user see?
  $currencies = currencies_load();
  //WHERE (
  //  (
  //    tw.field_worth_currcode = 1
  //  ) AND (//$currcode_and_states
  //    (
  //      (
  //        mcapi_transactions.state = 0
  //      ) AND (// $state_and_callback
  //        callback
  //      )
  //    ) OR (//$state_or_state
  //      (
  //        mcapi_transactions.state = 1
  //      ) AND (// $state_and_callback
  //        callback
  //      )
  //   )
  // ) OR ( //$or_currcodes
  //  currcode = 2 ...
  $or_currcodes = db_or();
  foreach ($currencies as $currency) {
    $currcode_and_states = db_and();
    $currcode_and_states->condition('tw.worth_currcode', $currency->currcode);
    //note for views we are only interested in viewing permission
    $state_or_state = db_or();
    foreach ($currency->view as $state => $callback) {
      $callback .= '_views';
      $state_and_callback = $callback($state);
      $state_or_state->condition($state_and_callback);
    }
    $currcode_and_states->condition($state_or_state);
    $or_currcodes->condition($currcode_and_states);
  }
  $query->condition($or_currcodes);
}
