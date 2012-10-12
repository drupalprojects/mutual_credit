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
        'field' => 'serial',
      ),
    ),
    'entity type' => 1,//so the entity module can expose this table
  );
  $data['mcapi_transactions']['serial'] = array(
    'title' => t('Serial number'), // The item it appears as on the UI,
    'field' => array(
      'help' => t('The serial number of the transaction and dependents'),
      'handler' => 'views_handler_field_transaction',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  $data['mcapi_transactions']['payer'] = array(
    'title' => t('Payer'),
    'help' => t('The user who gave currency in the transaction.'),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
      'click sortable' => TRUE,
    ),
    'field' => array(
      'handler' => 'views_handler_field',
    ),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'users',
      'label' => t('Payer'),
    ),
  );
  $data['mcapi_transactions']['payee'] = array(
    'title' => t('Payee'),
    'help' => t('The user who gave currency in the transaction.'),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
      'click sortable' => TRUE,
    ),
    'field' => array(
      'handler' => 'views_handler_field',
    ),
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
      'handler' => 'views_handler_argument_mcapi_date',
    )
  );
  if (module_exists('date_views')) {
    $data['mcapi_transactions']['created']['argument'] = array(
      'help' => t('Filter by month'),
      'handler' => 'views_handler_argument_mcapi_date',
      'fieldname' => 'created'//for some reason this wasn't showing in $view->real_field
    );
  }

  $data['mcapi_transactions']['view'] = array(
    'title' => t('View'),
    'help' => t("Link to transaction"),
    'field' => array(
      'handler' => 'views_handler_field_transaction',
    )
  );
  $data['mcapi_transactions']['undo'] = array(
    'title' => t('Undo'),
    'help' => t("Link to undo transaction"),
    'field' => array(
      'handler' => 'views_handler_field_transaction',
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


function transaction_access_participants_views($state) {
  $condition = db_and();
  $condition->condition('mcapi_transactions.state', $state);
  $subcondition = db_or();
  $subcondition->condition('mcapi_transactions.payee', $GLOBALS['user']->uid);
  $subcondition->condition('mcapi_transactions.payer', $GLOBALS['user']->uid);
  $condition->condition($subcondition);
  return $condition;
}

//returns conditions that $state = state && the current user ID  > 0
function transaction_access_authenticated_views($state){
  $condition = db_and();
  $condition->condition('mcapi_transactions.state', $state);
  $condition->condition(0, $GLOBALS['user']->uid, '<>'); //user is logged in
  return $condition;
}

function transaction_access_accountant_views($state) {
  $condition = db_and();
  $condition->condition('mcapi_transactions.state', $state);
  $condition->condition(1, user_access('administer all transactions')); //user is accountant
  return $condition;
}
function transaction_access_anyone_views($state) {
  $condition = db_and();
  $condition->condition('mcapi_transactions.state', $state);
  return $condition;
}
function transaction_access_payee_views($state) {
  $condition = db_and();
  $condition->condition('mcapi_transactions.state', $state);
  $condition->condition('mcapi_transactions.payee', $GLOBALS['user']->uid);
  return $condition;
}
function transaction_access_payer_views($state) {
  $condition = db_and();
  $condition->condition('mcapi_transactions.state', $state);
  $condition->condition('mcapi_transactions.payer', $GLOBALS['user']->uid);
  return $condition;
}

/*
 * implements hook_query_TAG_alter
 * the saved view adds the tag 'transaction_access'
 * this modifies the query only to show the transactions visible to the user.
 * this is pretty elaborate, but the access control has to be done in the query or views pager won't know how many results there were
 */
function mcapi_query_transaction_access_views_alter(QueryAlterableInterface $query) {
  $query->addJoin('LEFT', 'field_data_worth', 'tw', 'tw.entity_id = xid');
  $states = array_keys(mcapi_get_states());
  //what currencies can the current user see?
  $currencies = currencies_load($GLOBALS['user']);
  $or_currcodes = db_or();//where currcode = A OR currcode = B
  foreach ($currencies as $currcode => $currency) {
    $currcode_and_states = db_and(); //where (currcode = A AND (STATES) )
    $currcode_and_states->condition('tw.worth_currcode', $currcode);
    $state_or_state = db_or();//where state = A OR B
    foreach ($states as $state) {
      $callbacks = array_filter($currency->transaction_access[$state]);
      foreach ($callbacks as $callback) {
        $callback .= '_views';
        $state_and_callback = $callback($state);
        $state_or_state->condition($state_and_callback);
      }
    }
    $currcode_and_states->condition($state_or_state);
    $or_currcodes->condition($currcode_and_states);
  }
  //to see this working, show the sql query on admin/structure/views/settings/basic
  $query->condition($or_currcodes);
}
