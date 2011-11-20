<?php
//Id;

function mcapi_views_views_default_views() {
  $viewnames = array('user_trade_summary', 'transactions_summary', 'user_transactions', 'user_transactions_index', 'user_rankings', 'balance_history', 'transactions_all', 'user_trade');
  foreach ($viewnames as $viewname) {
    $function  = 'mcapi_getview_'.$viewname;
    $views[$viewname] = $function();
  }
  return $views;
}

function mcapi_getview_user_trade_summary() {
  $view = new view;
  $view->name = 'user_transaction_summaries';
  $view->description = 'per currency transaction summary for individual users';
  $view->tag = 'mcapi';
  $view->base_table = 'mcapi_index';
  $view->human_name = 'User transaction summaries';
  $view->core = 7;
  $view->api_version = '3.0';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Master */
  $handler = $view->new_display('default', 'Master', 'default');
  $handler->display->display_options['group_by'] = TRUE;
  $handler->display->display_options['access']['type'] = 'none';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['query']['options']['query_comment'] = FALSE;
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'full';
  $handler->display->display_options['style_plugin'] = 'default';
  $handler->display->display_options['row_plugin'] = 'fields';
  /* Relationship: Transaction index: 1st user */
  $handler->display->display_options['relationships']['uid1']['id'] = 'uid1';
  $handler->display->display_options['relationships']['uid1']['table'] = 'mcapi_index';
  $handler->display->display_options['relationships']['uid1']['field'] = 'uid1';
  $handler->display->display_options['relationships']['uid1']['required'] = 1;
  /* Relationship: Transaction index: 2nd user */
  $handler->display->display_options['relationships']['uid1']['id'] = 'uid2';
  $handler->display->display_options['relationships']['uid1']['table'] = 'mcapi_index';
  $handler->display->display_options['relationships']['uid1']['field'] = 'uid2';
  $handler->display->display_options['relationships']['uid1']['required'] = 1;
  /* Field: Transaction index: Currency */
  $handler->display->display_options['fields']['currcode']['id'] = 'currcode';
  $handler->display->display_options['fields']['currcode']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['currcode']['field'] = 'currcode';
  $handler->display->display_options['fields']['currcode']['label'] = '';
  $handler->display->display_options['fields']['currcode']['alter']['alter_text'] = 1;
  $handler->display->display_options['fields']['currcode']['alter']['text'] = '<strong>[currcode]</strong>';
  $handler->display->display_options['fields']['currcode']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['currcode']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['currcode']['alter']['external'] = 0;
  $handler->display->display_options['fields']['currcode']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['currcode']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['currcode']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['currcode']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['currcode']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['currcode']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['currcode']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['currcode']['alter']['html'] = 0;
  $handler->display->display_options['fields']['currcode']['element_label_colon'] = FALSE;
  $handler->display->display_options['fields']['currcode']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['currcode']['hide_empty'] = 0;
  $handler->display->display_options['fields']['currcode']['empty_zero'] = 0;
  $handler->display->display_options['fields']['currcode']['hide_alter_empty'] = 0;
  /* Field: COUNT(DISTINCT User: Uid) */
  $handler->display->display_options['fields']['uid']['id'] = 'uid';
  $handler->display->display_options['fields']['uid']['table'] = 'users';
  $handler->display->display_options['fields']['uid']['field'] = 'uid';
  $handler->display->display_options['fields']['uid']['relationship'] = 'uid2';
  $handler->display->display_options['fields']['uid']['group_type'] = 'count_distinct';
  $handler->display->display_options['fields']['uid']['label'] = 'Promiscuity';
  $handler->display->display_options['fields']['uid']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['uid']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['uid']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['uid']['alter']['external'] = 0;
  $handler->display->display_options['fields']['uid']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['uid']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['uid']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['uid']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['uid']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['uid']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['uid']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['uid']['alter']['html'] = 0;
  $handler->display->display_options['fields']['uid']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['uid']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['uid']['hide_empty'] = 0;
  $handler->display->display_options['fields']['uid']['empty_zero'] = 0;
  $handler->display->display_options['fields']['uid']['hide_alter_empty'] = 0;
  $handler->display->display_options['fields']['uid']['format_plural'] = 0;
  /* Field: SUM(Transaction index: Expenditure) */
  $handler->display->display_options['fields']['expenditure']['id'] = 'expenditure';
  $handler->display->display_options['fields']['expenditure']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['expenditure']['field'] = 'expenditure';
  $handler->display->display_options['fields']['expenditure']['group_type'] = 'sum';
  $handler->display->display_options['fields']['expenditure']['label'] = 'Gross expenditure';
  $handler->display->display_options['fields']['expenditure']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['expenditure']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['expenditure']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['expenditure']['alter']['external'] = 0;
  $handler->display->display_options['fields']['expenditure']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['expenditure']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['expenditure']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['expenditure']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['expenditure']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['expenditure']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['expenditure']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['expenditure']['alter']['html'] = 0;
  $handler->display->display_options['fields']['expenditure']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['expenditure']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['expenditure']['hide_empty'] = 0;
  $handler->display->display_options['fields']['expenditure']['empty_zero'] = 0;
  /* Field: SUM(Transaction index: Income) */
  $handler->display->display_options['fields']['income']['id'] = 'income';
  $handler->display->display_options['fields']['income']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['income']['field'] = 'income';
  $handler->display->display_options['fields']['income']['group_type'] = 'sum';
  $handler->display->display_options['fields']['income']['label'] = 'Gross income';
  $handler->display->display_options['fields']['income']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['income']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['income']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['income']['alter']['external'] = 0;
  $handler->display->display_options['fields']['income']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['income']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['income']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['income']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['income']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['income']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['income']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['income']['alter']['html'] = 0;
  $handler->display->display_options['fields']['income']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['income']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['income']['hide_empty'] = 0;
  $handler->display->display_options['fields']['income']['empty_zero'] = 0;
  /* Field: COUNT(User: Uid) */
  $handler->display->display_options['fields']['uid_1']['id'] = 'uid_1';
  $handler->display->display_options['fields']['uid_1']['table'] = 'users';
  $handler->display->display_options['fields']['uid_1']['field'] = 'uid';
  $handler->display->display_options['fields']['uid_1']['relationship'] = 'uid1';
  $handler->display->display_options['fields']['uid_1']['group_type'] = 'count';
  $handler->display->display_options['fields']['uid_1']['label'] = 'Trades';
  $handler->display->display_options['fields']['uid_1']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['uid_1']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['uid_1']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['uid_1']['alter']['external'] = 0;
  $handler->display->display_options['fields']['uid_1']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['uid_1']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['uid_1']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['uid_1']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['uid_1']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['uid_1']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['uid_1']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['uid_1']['alter']['html'] = 0;
  $handler->display->display_options['fields']['uid_1']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['uid_1']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['uid_1']['hide_empty'] = 0;
  $handler->display->display_options['fields']['uid_1']['empty_zero'] = 0;
  $handler->display->display_options['fields']['uid_1']['hide_alter_empty'] = 0;
  $handler->display->display_options['fields']['uid_1']['format_plural'] = 0;
  /* Contextual filter: User: Uid */
  $handler->display->display_options['arguments']['uid']['id'] = 'uid';
  $handler->display->display_options['arguments']['uid']['table'] = 'users';
  $handler->display->display_options['arguments']['uid']['field'] = 'uid';
  $handler->display->display_options['arguments']['uid']['relationship'] = 'uid1';
  $handler->display->display_options['arguments']['uid']['default_action'] = 'default';
  $handler->display->display_options['arguments']['uid']['default_argument_type'] = 'user';
  $handler->display->display_options['arguments']['uid']['default_argument_options']['user'] = FALSE;
  $handler->display->display_options['arguments']['uid']['default_argument_skip_url'] = 0;
  $handler->display->display_options['arguments']['uid']['summary']['number_of_records'] = '0';
  $handler->display->display_options['arguments']['uid']['summary']['format'] = 'default_summary';
  $handler->display->display_options['arguments']['uid']['summary_options']['items_per_page'] = '25';
  $handler->display->display_options['arguments']['uid']['break_phrase'] = 0;
  $handler->display->display_options['arguments']['uid']['not'] = 0;

  /* Display: Block */
  $handler = $view->new_display('block', 'Block', 'block_1');
  $handler->display->display_options['block_description'] = 'User transactions summary per currency';

  /* Display: Page */
  $handler = $view->new_display('page', 'Page', 'page_1');
  $translatables['user_trade_summary'] = array(
    t('Master'),
    t('more'),
    t('Apply'),
    t('Reset'),
    t('Sort by'),
    t('Asc'),
    t('Desc'),
    t('Items per page'),
    t('- All -'),
    t('Offset'),
    t('Promiscuity'),
    t('Gross expenditure'),
    t('Gross income'),
    t('Trades'),
    t('Currency'),
    t('All'),
  );

  return $view;
}
function mcapi_getview_transactions_summary() {
  $view = new view;
  $view->name = 'transactions_summary';
  $view->description = '';
  $view->tag = 'mcapi';
  $view->base_table = 'mcapi_transactions';
  $view->human_name = 'transactions summary';
  $view->core = 7;
  $view->api_version = '3.0-alpha1';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Master */
  $handler = $view->new_display('default', 'Master', 'default');
  $handler->display->display_options['group_by'] = TRUE;
  $handler->display->display_options['access']['type'] = 'none';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['query']['options']['query_comment'] = FALSE;
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'full';
  $handler->display->display_options['style_plugin'] = 'default';
  $handler->display->display_options['row_plugin'] = 'fields';
  /* Field: COUNT(Transaction: value) */
  $handler->display->display_options['fields']['quantity']['id'] = 'quantity';
  $handler->display->display_options['fields']['quantity']['table'] = 'mcapi_transactions';
  $handler->display->display_options['fields']['quantity']['field'] = 'quantity';
  $handler->display->display_options['fields']['quantity']['group_type'] = 'count';
  $handler->display->display_options['fields']['quantity']['label'] = 'Trades';
  $handler->display->display_options['fields']['quantity']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['external'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['quantity']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['quantity']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['html'] = 0;
  $handler->display->display_options['fields']['quantity']['element_label_colon'] = 0;
  $handler->display->display_options['fields']['quantity']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['quantity']['hide_empty'] = 0;
  $handler->display->display_options['fields']['quantity']['empty_zero'] = 0;
  $handler->display->display_options['fields']['quantity']['format_plural'] = 0;
  /* Field: SUM(Transaction: value) */
  $handler->display->display_options['fields']['quantity_1']['id'] = 'quantity_1';
  $handler->display->display_options['fields']['quantity_1']['table'] = 'mcapi_transactions';
  $handler->display->display_options['fields']['quantity_1']['field'] = 'quantity';
  $handler->display->display_options['fields']['quantity_1']['group_type'] = 'sum';
  $handler->display->display_options['fields']['quantity_1']['label'] = 'Volume';
  $handler->display->display_options['fields']['quantity_1']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['quantity_1']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['quantity_1']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['quantity_1']['alter']['external'] = 0;
  $handler->display->display_options['fields']['quantity_1']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['quantity_1']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['quantity_1']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['quantity_1']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['quantity_1']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['quantity_1']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['quantity_1']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['quantity_1']['alter']['html'] = 0;
  $handler->display->display_options['fields']['quantity_1']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['quantity_1']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['quantity_1']['hide_empty'] = 0;
  $handler->display->display_options['fields']['quantity_1']['empty_zero'] = 0;
  /* Field: COUNT(DISTINCT Transaction index: 1st user) */
  $handler->display->display_options['fields']['uid1']['id'] = 'uid1';
  $handler->display->display_options['fields']['uid1']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['uid1']['field'] = 'uid1';
  $handler->display->display_options['fields']['uid1']['group_type'] = 'count_distinct';
  $handler->display->display_options['fields']['uid1']['label'] = 'Active users';
  $handler->display->display_options['fields']['uid1']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['uid1']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['uid1']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['uid1']['alter']['external'] = 0;
  $handler->display->display_options['fields']['uid1']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['uid1']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['uid1']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['uid1']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['uid1']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['uid1']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['uid1']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['uid1']['alter']['html'] = 0;
  $handler->display->display_options['fields']['uid1']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['uid1']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['uid1']['hide_empty'] = 0;
  $handler->display->display_options['fields']['uid1']['empty_zero'] = 0;
  $handler->display->display_options['fields']['uid1']['format_plural'] = 0;
  $handler->display->display_options['filters']['currcode']['id'] = 'currcode';
  $handler->display->display_options['filters']['currcode']['table'] = 'mcapi_transactions';
  $handler->display->display_options['filters']['currcode']['field'] = 'currcode';
  $handler->display->display_options['filters']['currcode']['exposed'] = TRUE;
  $handler->display->display_options['filters']['currcode']['expose']['operator_id'] = 'currcode_op';
  $handler->display->display_options['filters']['currcode']['expose']['label'] = 'Currency ID';
  $handler->display->display_options['filters']['currcode']['expose']['operator'] = 'currcode_op';
  $handler->display->display_options['filters']['currcode']['expose']['identifier'] = 'currcode';
  $handler->display->display_options['filters']['currcode']['expose']['reduce'] = 0;
  /* Filter criterion: Transaction: created */
  $handler->display->display_options['filters']['created']['id'] = 'created';
  $handler->display->display_options['filters']['created']['table'] = 'mcapi_transactions';
  $handler->display->display_options['filters']['created']['field'] = 'created';
  $handler->display->display_options['filters']['created']['operator'] = 'between';
  $handler->display->display_options['filters']['created']['exposed'] = TRUE;
  $handler->display->display_options['filters']['created']['expose']['operator_id'] = 'created_op';
  $handler->display->display_options['filters']['created']['expose']['label'] = 'created';
  $handler->display->display_options['filters']['created']['expose']['operator'] = 'created_op';
  $handler->display->display_options['filters']['created']['expose']['identifier'] = 'created';
  $handler->display->display_options['filters']['created']['expose']['multiple'] = FALSE;

  /* Display: Block */
  $handler = $view->new_display('block', 'Block', 'block_1');
  $handler->display->display_options['block_description'] = 'User transactions summary per currency';
  $translatables['transactions_summary'] = array(
    t('Master'),
    t('more'),
    t('Apply'),
    t('Reset'),
    t('Sort by'),
    t('Asc'),
    t('Desc'),
    t('Items per page'),
    t('- All -'),
    t('Offset'),
    t('Trades'),
    t('Volume'),
    t('Active users'),
    t('Currency ID'),
    t('created'),
    t('Page'),
  );
  return $view;
}

function mcapi_getview_user_transactions() {
  $view = new view;
  $view->name = 'user_transactions';
  $view->description = 'shows trades of one user, with otheruser field and running balance (reads transaction index table)';
  $view->tag = 'default';
  $view->base_table = 'mcapi_index';
  $view->human_name = 'User transactions';
  $view->core = 7;
  $view->api_version = '3.0';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Master */
  $handler = $view->new_display('default', 'Master', 'default');
  $handler->display->display_options['title'] = 'User transactions';
  $handler->display->display_options['access']['type'] = 'none';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['query']['options']['query_comment'] = FALSE;
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'full';
  $handler->display->display_options['pager']['options']['items_per_page'] = '25';
  $handler->display->display_options['style_plugin'] = 'table';
  /* Relationship: Transaction index: 1st user */
  $handler->display->display_options['relationships']['uid1']['id'] = 'uid1';
  $handler->display->display_options['relationships']['uid1']['table'] = 'mcapi_index';
  $handler->display->display_options['relationships']['uid1']['field'] = 'uid1';
  $handler->display->display_options['relationships']['uid1']['required'] = 1;
  /* Relationship: Transaction index: 2nd user */
  $handler->display->display_options['relationships']['uid2']['id'] = 'uid2';
  $handler->display->display_options['relationships']['uid2']['table'] = 'mcapi_index';
  $handler->display->display_options['relationships']['uid2']['field'] = 'uid2';
  $handler->display->display_options['relationships']['uid2']['required'] = 1;
  /* Field: Transaction index: Created */
  $handler->display->display_options['fields']['created']['id'] = 'created';
  $handler->display->display_options['fields']['created']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['created']['field'] = 'created';
  $handler->display->display_options['fields']['created']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['created']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['created']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['created']['alter']['external'] = 0;
  $handler->display->display_options['fields']['created']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['created']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['created']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['created']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['created']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['created']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['created']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['created']['alter']['html'] = 0;
  $handler->display->display_options['fields']['created']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['created']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['created']['hide_empty'] = 0;
  $handler->display->display_options['fields']['created']['empty_zero'] = 0;
  $handler->display->display_options['fields']['created']['hide_alter_empty'] = 0;
  $handler->display->display_options['fields']['created']['date_format'] = 'long';
  /* Field: User: Name */
  $handler->display->display_options['fields']['name']['id'] = 'name';
  $handler->display->display_options['fields']['name']['table'] = 'users';
  $handler->display->display_options['fields']['name']['field'] = 'name';
  $handler->display->display_options['fields']['name']['relationship'] = 'uid2';
  $handler->display->display_options['fields']['name']['label'] = 'With';
  $handler->display->display_options['fields']['name']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['name']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['name']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['name']['alter']['external'] = 0;
  $handler->display->display_options['fields']['name']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['name']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['name']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['name']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['name']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['name']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['name']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['name']['alter']['html'] = 0;
  $handler->display->display_options['fields']['name']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['name']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['name']['hide_empty'] = 0;
  $handler->display->display_options['fields']['name']['empty_zero'] = 0;
  $handler->display->display_options['fields']['name']['hide_alter_empty'] = 0;
  $handler->display->display_options['fields']['name']['link_to_user'] = 1;
  $handler->display->display_options['fields']['name']['overwrite_anonymous'] = 0;
  $handler->display->display_options['fields']['name']['format_username'] = 1;
  /* Field: Transaction index: Value */
  $handler->display->display_options['fields']['diff']['id'] = 'diff';
  $handler->display->display_options['fields']['diff']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['diff']['field'] = 'diff';
  $handler->display->display_options['fields']['diff']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['diff']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['diff']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['diff']['alter']['external'] = 0;
  $handler->display->display_options['fields']['diff']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['diff']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['diff']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['diff']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['diff']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['diff']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['diff']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['diff']['alter']['html'] = 0;
  $handler->display->display_options['fields']['diff']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['diff']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['diff']['hide_empty'] = 0;
  $handler->display->display_options['fields']['diff']['empty_zero'] = 0;
  $handler->display->display_options['fields']['diff']['hide_alter_empty'] = 0;
  /* Field: Transaction index: Balance */
  $handler->display->display_options['fields']['balance']['id'] = 'balance';
  $handler->display->display_options['fields']['balance']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['balance']['field'] = 'balance';
  $handler->display->display_options['fields']['balance']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['balance']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['balance']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['balance']['alter']['external'] = 0;
  $handler->display->display_options['fields']['balance']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['balance']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['balance']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['balance']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['balance']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['balance']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['balance']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['balance']['alter']['html'] = 0;
  $handler->display->display_options['fields']['balance']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['balance']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['balance']['hide_empty'] = 0;
  $handler->display->display_options['fields']['balance']['empty_zero'] = 0;
  $handler->display->display_options['fields']['balance']['hide_alter_empty'] = 0;
  /* Sort criterion: Transaction index: Created */
  $handler->display->display_options['sorts']['created']['id'] = 'created';
  $handler->display->display_options['sorts']['created']['table'] = 'mcapi_index';
  $handler->display->display_options['sorts']['created']['field'] = 'created';
  $handler->display->display_options['sorts']['created']['order'] = 'DESC';
  /* Contextual filter: User: Uid */
  $handler->display->display_options['arguments']['uid']['id'] = 'uid';
  $handler->display->display_options['arguments']['uid']['table'] = 'users';
  $handler->display->display_options['arguments']['uid']['field'] = 'uid';
  $handler->display->display_options['arguments']['uid']['relationship'] = 'uid1';
  $handler->display->display_options['arguments']['uid']['default_action'] = 'not found';
  $handler->display->display_options['arguments']['uid']['default_argument_type'] = 'fixed';
  $handler->display->display_options['arguments']['uid']['default_argument_skip_url'] = 0;
  $handler->display->display_options['arguments']['uid']['summary']['number_of_records'] = '0';
  $handler->display->display_options['arguments']['uid']['summary']['format'] = 'default_summary';
  $handler->display->display_options['arguments']['uid']['summary_options']['items_per_page'] = '25';
  $handler->display->display_options['arguments']['uid']['break_phrase'] = 0;
  $handler->display->display_options['arguments']['uid']['not'] = 0;
  /* Filter criterion: Transaction index: Currency */
  $handler->display->display_options['filters']['currcode']['id'] = 'currcode';
  $handler->display->display_options['filters']['currcode']['table'] = 'mcapi_index';
  $handler->display->display_options['filters']['currcode']['field'] = 'currcode';
  $handler->display->display_options['filters']['currcode']['exposed'] = TRUE;
  $handler->display->display_options['filters']['currcode']['expose']['operator_id'] = '';
  $handler->display->display_options['filters']['currcode']['expose']['label'] = 'Currency';
  $handler->display->display_options['filters']['currcode']['expose']['operator'] = 'currcode_op';
  $handler->display->display_options['filters']['currcode']['expose']['identifier'] = 'currcode';
  $handler->display->display_options['filters']['currcode']['expose']['reduce'] = 0;

  /* Display: Page */
  $handler = $view->new_display('page', 'Page', 'page');
  $handler->display->display_options['path'] = 'user/%/trades';
  $handler->display->display_options['menu']['type'] = 'tab';
  $handler->display->display_options['menu']['title'] = 'Trades';
  $handler->display->display_options['menu']['weight'] = '0';
  $translatables['user_transactions'] = array(
    t('Master'),
    t('User transactions'),
    t('more'),
    t('Apply'),
    t('Reset'),
    t('Sort by'),
    t('Asc'),
    t('Desc'),
    t('Items per page'),
    t('- All -'),
    t('Offset'),
    t('1st user'),
    t('2nd user'),
    t('Created'),
    t('2nd Person'),
    t('Value'),
    t('Balance'),
    t('Name'),
    t('All'),
    t('Currency'),
    t('Page'),
  );
  return $view;
}

function mcapi_getview_user_rankings() {
  $view = new view;
  $view->name = 'user_rankings';
  $view->description = 'If you have multiple currencies, be sure to change the argument. Also you can add \'between\' dates';
  $view->tag = 'mcapi';
  $view->base_table = 'mcapi_index';
  $view->human_name = 'user_ranking';
  $view->core = 7;
  $view->api_version = '3.0';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Master */
  $handler = $view->new_display('default', 'Master', 'default');
  $handler->display->display_options['title'] = 'Most trades';
  $handler->display->display_options['group_by'] = TRUE;
  $handler->display->display_options['access']['type'] = 'perm';
  $handler->display->display_options['access']['perm'] = 'access user profiles';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['query']['options']['query_comment'] = FALSE;
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'full';
  $handler->display->display_options['style_plugin'] = 'list';
  $handler->display->display_options['style_options']['type'] = 'ol';
  $handler->display->display_options['row_plugin'] = 'fields';
  $handler->display->display_options['row_options']['inline'] = array(
    'name' => 'name',
  );
  $handler->display->display_options['row_options']['separator'] = ': ';
  $handler->display->display_options['row_options']['hide_empty'] = 0;
  $handler->display->display_options['row_options']['default_field_elements'] = 0;
  /* Relationship: Transaction index: 1st user */
  $handler->display->display_options['relationships']['uid1']['id'] = 'uid1';
  $handler->display->display_options['relationships']['uid1']['table'] = 'mcapi_index';
  $handler->display->display_options['relationships']['uid1']['field'] = 'uid1';
  $handler->display->display_options['relationships']['uid1']['required'] = 1;
  /* Relationship: Transaction index: 2nd user */
  $handler->display->display_options['relationships']['uid2']['id'] = 'uid2';
  $handler->display->display_options['relationships']['uid2']['table'] = 'mcapi_index';
  $handler->display->display_options['relationships']['uid2']['field'] = 'uid2';
  $handler->display->display_options['relationships']['uid2']['required'] = 1;
  /* Field: User: Name */
  $handler->display->display_options['fields']['name']['id'] = 'name';
  $handler->display->display_options['fields']['name']['table'] = 'users';
  $handler->display->display_options['fields']['name']['field'] = 'name';
  $handler->display->display_options['fields']['name']['relationship'] = 'uid1';
  $handler->display->display_options['fields']['name']['label'] = '';
  $handler->display->display_options['fields']['name']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['name']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['name']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['name']['alter']['external'] = 0;
  $handler->display->display_options['fields']['name']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['name']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['name']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['name']['alter']['word_boundary'] = 0;
  $handler->display->display_options['fields']['name']['alter']['ellipsis'] = 0;
  $handler->display->display_options['fields']['name']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['name']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['name']['alter']['html'] = 0;
  $handler->display->display_options['fields']['name']['element_label_colon'] = FALSE;
  $handler->display->display_options['fields']['name']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['name']['hide_empty'] = 0;
  $handler->display->display_options['fields']['name']['empty_zero'] = 0;
  $handler->display->display_options['fields']['name']['hide_alter_empty'] = 0;
  $handler->display->display_options['fields']['name']['link_to_user'] = 1;
  $handler->display->display_options['fields']['name']['overwrite_anonymous'] = 0;
  $handler->display->display_options['fields']['name']['format_username'] = 1;
  /* Field: SUM(Transaction index: Expenditure) */
  $handler->display->display_options['fields']['expenditure']['id'] = 'expenditure';
  $handler->display->display_options['fields']['expenditure']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['expenditure']['field'] = 'expenditure';
  $handler->display->display_options['fields']['expenditure']['group_type'] = 'sum';
  $handler->display->display_options['fields']['expenditure']['label'] = '';
  $handler->display->display_options['fields']['expenditure']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['expenditure']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['expenditure']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['expenditure']['alter']['external'] = 0;
  $handler->display->display_options['fields']['expenditure']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['expenditure']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['expenditure']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['expenditure']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['expenditure']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['expenditure']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['expenditure']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['expenditure']['alter']['html'] = 0;
  $handler->display->display_options['fields']['expenditure']['element_label_colon'] = FALSE;
  $handler->display->display_options['fields']['expenditure']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['expenditure']['hide_empty'] = 0;
  $handler->display->display_options['fields']['expenditure']['empty_zero'] = 0;
  /* Contextual filter: Transaction index: Currency */
  $handler->display->display_options['arguments']['currcode']['id'] = 'currcode';
  $handler->display->display_options['arguments']['currcode']['table'] = 'mcapi_index';
  $handler->display->display_options['arguments']['currcode']['field'] = 'currcode';
  $handler->display->display_options['arguments']['currcode']['default_action'] = 'default';
  $handler->display->display_options['arguments']['currcode']['default_argument_type'] = 'php';
  $handler->display->display_options['arguments']['currcode']['default_argument_options']['code'] = 'return array_shift(array_keys(currencies_load()));';
  $handler->display->display_options['arguments']['currcode']['default_argument_skip_url'] = 0;
  $handler->display->display_options['arguments']['currcode']['summary']['number_of_records'] = '0';
  $handler->display->display_options['arguments']['currcode']['summary']['format'] = 'default_summary';
  $handler->display->display_options['arguments']['currcode']['summary_options']['items_per_page'] = '25';

  /* Display: Promiscuity */
  $handler = $view->new_display('block', 'Promiscuity', 'block_1');
  $handler->display->display_options['defaults']['title'] = FALSE;
  $handler->display->display_options['title'] = 'Promiscuity';
  $handler->display->display_options['defaults']['fields'] = FALSE;
  /* Field: User: Name */
  $handler->display->display_options['fields']['name']['id'] = 'name';
  $handler->display->display_options['fields']['name']['table'] = 'users';
  $handler->display->display_options['fields']['name']['field'] = 'name';
  $handler->display->display_options['fields']['name']['relationship'] = 'uid1';
  $handler->display->display_options['fields']['name']['label'] = '';
  $handler->display->display_options['fields']['name']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['name']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['name']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['name']['alter']['external'] = 0;
  $handler->display->display_options['fields']['name']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['name']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['name']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['name']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['name']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['name']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['name']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['name']['alter']['html'] = 0;
  $handler->display->display_options['fields']['name']['element_label_colon'] = FALSE;
  $handler->display->display_options['fields']['name']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['name']['hide_empty'] = 0;
  $handler->display->display_options['fields']['name']['empty_zero'] = 0;
  $handler->display->display_options['fields']['name']['hide_alter_empty'] = 0;
  $handler->display->display_options['fields']['name']['link_to_user'] = 1;
  $handler->display->display_options['fields']['name']['overwrite_anonymous'] = 0;
  $handler->display->display_options['fields']['name']['format_username'] = 1;
  /* Field: COUNT(DISTINCT User: Uid) */
  $handler->display->display_options['fields']['uid']['id'] = 'uid';
  $handler->display->display_options['fields']['uid']['table'] = 'users';
  $handler->display->display_options['fields']['uid']['field'] = 'uid';
  $handler->display->display_options['fields']['uid']['relationship'] = 'uid2';
  $handler->display->display_options['fields']['uid']['group_type'] = 'count_distinct';
  $handler->display->display_options['fields']['uid']['label'] = '';
  $handler->display->display_options['fields']['uid']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['uid']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['uid']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['uid']['alter']['external'] = 0;
  $handler->display->display_options['fields']['uid']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['uid']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['uid']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['uid']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['uid']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['uid']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['uid']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['uid']['alter']['html'] = 0;
  $handler->display->display_options['fields']['uid']['element_label_colon'] = FALSE;
  $handler->display->display_options['fields']['uid']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['uid']['hide_empty'] = 0;
  $handler->display->display_options['fields']['uid']['empty_zero'] = 0;
  $handler->display->display_options['fields']['uid']['hide_alter_empty'] = 0;
  $handler->display->display_options['fields']['uid']['format_plural'] = 0;
  $handler->display->display_options['defaults']['sorts'] = FALSE;
  /* Sort criterion: COUNT(DISTINCT User: Uid) */
  $handler->display->display_options['sorts']['uid']['id'] = 'uid';
  $handler->display->display_options['sorts']['uid']['table'] = 'users';
  $handler->display->display_options['sorts']['uid']['field'] = 'uid';
  $handler->display->display_options['sorts']['uid']['relationship'] = 'uid2';
  $handler->display->display_options['sorts']['uid']['group_type'] = 'count_distinct';
  $handler->display->display_options['sorts']['uid']['order'] = 'DESC';

  /* Display: Volume */
  $handler = $view->new_display('block', 'Volume', 'block_2');
  $handler->display->display_options['defaults']['title'] = FALSE;
  $handler->display->display_options['title'] = 'Volume';
  $handler->display->display_options['defaults']['fields'] = FALSE;
  /* Field: User: Name */
  $handler->display->display_options['fields']['name']['id'] = 'name';
  $handler->display->display_options['fields']['name']['table'] = 'users';
  $handler->display->display_options['fields']['name']['field'] = 'name';
  $handler->display->display_options['fields']['name']['relationship'] = 'uid1';
  $handler->display->display_options['fields']['name']['label'] = '';
  $handler->display->display_options['fields']['name']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['name']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['name']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['name']['alter']['external'] = 0;
  $handler->display->display_options['fields']['name']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['name']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['name']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['name']['alter']['word_boundary'] = 0;
  $handler->display->display_options['fields']['name']['alter']['ellipsis'] = 0;
  $handler->display->display_options['fields']['name']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['name']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['name']['alter']['html'] = 0;
  $handler->display->display_options['fields']['name']['element_label_colon'] = FALSE;
  $handler->display->display_options['fields']['name']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['name']['hide_empty'] = 0;
  $handler->display->display_options['fields']['name']['empty_zero'] = 0;
  $handler->display->display_options['fields']['name']['hide_alter_empty'] = 0;
  $handler->display->display_options['fields']['name']['link_to_user'] = 1;
  $handler->display->display_options['fields']['name']['overwrite_anonymous'] = 0;
  $handler->display->display_options['fields']['name']['format_username'] = 1;
  /* Field: SUM(Transaction index: Volume) */
  $handler->display->display_options['fields']['volume']['id'] = 'volume';
  $handler->display->display_options['fields']['volume']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['volume']['field'] = 'volume';
  $handler->display->display_options['fields']['volume']['group_type'] = 'sum';
  $handler->display->display_options['fields']['volume']['label'] = '';
  $handler->display->display_options['fields']['volume']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['volume']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['volume']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['volume']['alter']['external'] = 0;
  $handler->display->display_options['fields']['volume']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['volume']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['volume']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['volume']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['volume']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['volume']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['volume']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['volume']['alter']['html'] = 0;
  $handler->display->display_options['fields']['volume']['element_label_colon'] = FALSE;
  $handler->display->display_options['fields']['volume']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['volume']['hide_empty'] = 0;
  $handler->display->display_options['fields']['volume']['empty_zero'] = 0;
  $handler->display->display_options['defaults']['sorts'] = FALSE;
  /* Sort criterion: SUM(Transaction index: Volume) */
  $handler->display->display_options['sorts']['volume']['id'] = 'volume';
  $handler->display->display_options['sorts']['volume']['table'] = 'mcapi_index';
  $handler->display->display_options['sorts']['volume']['field'] = 'volume';
  $handler->display->display_options['sorts']['volume']['group_type'] = 'sum';
  $handler->display->display_options['sorts']['volume']['order'] = 'DESC';

  /* Display: Trades */
  $handler = $view->new_display('block', 'Trades', 'block_3');
  $handler->display->display_options['display_description'] = 'Number of trades';
  $handler->display->display_options['defaults']['fields'] = FALSE;
  /* Field: User: Name */
  $handler->display->display_options['fields']['name']['id'] = 'name';
  $handler->display->display_options['fields']['name']['table'] = 'users';
  $handler->display->display_options['fields']['name']['field'] = 'name';
  $handler->display->display_options['fields']['name']['relationship'] = 'uid1';
  $handler->display->display_options['fields']['name']['label'] = '';
  $handler->display->display_options['fields']['name']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['name']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['name']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['name']['alter']['external'] = 0;
  $handler->display->display_options['fields']['name']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['name']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['name']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['name']['alter']['word_boundary'] = 0;
  $handler->display->display_options['fields']['name']['alter']['ellipsis'] = 0;
  $handler->display->display_options['fields']['name']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['name']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['name']['alter']['html'] = 0;
  $handler->display->display_options['fields']['name']['element_label_colon'] = FALSE;
  $handler->display->display_options['fields']['name']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['name']['hide_empty'] = 0;
  $handler->display->display_options['fields']['name']['empty_zero'] = 0;
  $handler->display->display_options['fields']['name']['hide_alter_empty'] = 0;
  $handler->display->display_options['fields']['name']['link_to_user'] = 1;
  $handler->display->display_options['fields']['name']['overwrite_anonymous'] = 0;
  $handler->display->display_options['fields']['name']['format_username'] = 1;
  /* Field: COUNT(User: Name) */
  $handler->display->display_options['fields']['name_1']['id'] = 'name_1';
  $handler->display->display_options['fields']['name_1']['table'] = 'users';
  $handler->display->display_options['fields']['name_1']['field'] = 'name';
  $handler->display->display_options['fields']['name_1']['relationship'] = 'uid1';
  $handler->display->display_options['fields']['name_1']['group_type'] = 'count';
  $handler->display->display_options['fields']['name_1']['label'] = '';
  $handler->display->display_options['fields']['name_1']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['name_1']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['name_1']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['name_1']['alter']['external'] = 0;
  $handler->display->display_options['fields']['name_1']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['name_1']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['name_1']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['name_1']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['name_1']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['name_1']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['name_1']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['name_1']['alter']['html'] = 0;
  $handler->display->display_options['fields']['name_1']['element_label_colon'] = FALSE;
  $handler->display->display_options['fields']['name_1']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['name_1']['hide_empty'] = 0;
  $handler->display->display_options['fields']['name_1']['empty_zero'] = 0;
  $handler->display->display_options['fields']['name_1']['hide_alter_empty'] = 0;
  $handler->display->display_options['fields']['name_1']['format_plural'] = 0;
  $handler->display->display_options['defaults']['sorts'] = FALSE;
  /* Sort criterion: COUNT(User: Name) */
  $handler->display->display_options['sorts']['name']['id'] = 'name';
  $handler->display->display_options['sorts']['name']['table'] = 'users';
  $handler->display->display_options['sorts']['name']['field'] = 'name';
  $handler->display->display_options['sorts']['name']['relationship'] = 'uid1';
  $handler->display->display_options['sorts']['name']['group_type'] = 'count';
  $handler->display->display_options['sorts']['name']['order'] = 'DESC';

  /* Display: Incomes */
  $handler = $view->new_display('block', 'Incomes', 'block_4');
  $handler->display->display_options['defaults']['title'] = FALSE;
  $handler->display->display_options['title'] = 'Incomes';
  $handler->display->display_options['defaults']['fields'] = FALSE;
  /* Field: User: Name */
  $handler->display->display_options['fields']['name']['id'] = 'name';
  $handler->display->display_options['fields']['name']['table'] = 'users';
  $handler->display->display_options['fields']['name']['field'] = 'name';
  $handler->display->display_options['fields']['name']['relationship'] = 'uid1';
  $handler->display->display_options['fields']['name']['label'] = '';
  $handler->display->display_options['fields']['name']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['name']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['name']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['name']['alter']['external'] = 0;
  $handler->display->display_options['fields']['name']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['name']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['name']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['name']['alter']['word_boundary'] = 0;
  $handler->display->display_options['fields']['name']['alter']['ellipsis'] = 0;
  $handler->display->display_options['fields']['name']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['name']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['name']['alter']['html'] = 0;
  $handler->display->display_options['fields']['name']['element_label_colon'] = FALSE;
  $handler->display->display_options['fields']['name']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['name']['hide_empty'] = 0;
  $handler->display->display_options['fields']['name']['empty_zero'] = 0;
  $handler->display->display_options['fields']['name']['hide_alter_empty'] = 0;
  $handler->display->display_options['fields']['name']['link_to_user'] = 1;
  $handler->display->display_options['fields']['name']['overwrite_anonymous'] = 0;
  $handler->display->display_options['fields']['name']['format_username'] = 1;
  /* Field: SUM(Transaction index: Income) */
  $handler->display->display_options['fields']['income']['id'] = 'income';
  $handler->display->display_options['fields']['income']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['income']['field'] = 'income';
  $handler->display->display_options['fields']['income']['group_type'] = 'sum';
  $handler->display->display_options['fields']['income']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['income']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['income']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['income']['alter']['external'] = 0;
  $handler->display->display_options['fields']['income']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['income']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['income']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['income']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['income']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['income']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['income']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['income']['alter']['html'] = 0;
  $handler->display->display_options['fields']['income']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['income']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['income']['hide_empty'] = 0;
  $handler->display->display_options['fields']['income']['empty_zero'] = 0;
  $handler->display->display_options['defaults']['sorts'] = FALSE;
  /* Sort criterion: SUM(Transaction index: Income) */
  $handler->display->display_options['sorts']['income']['id'] = 'income';
  $handler->display->display_options['sorts']['income']['table'] = 'mcapi_index';
  $handler->display->display_options['sorts']['income']['field'] = 'income';
  $handler->display->display_options['sorts']['income']['group_type'] = 'sum';
  $handler->display->display_options['sorts']['income']['order'] = 'DESC';

  /* Display: Expenditure */
  $handler = $view->new_display('block', 'Expenditure', 'block_5');
  $handler->display->display_options['defaults']['title'] = FALSE;
  $handler->display->display_options['title'] = 'expenditures';
  $handler->display->display_options['defaults']['sorts'] = FALSE;
  /* Sort criterion: SUM(Transaction index: Expenditure) */
  $handler->display->display_options['sorts']['expenditure']['id'] = 'expenditure';
  $handler->display->display_options['sorts']['expenditure']['table'] = 'mcapi_index';
  $handler->display->display_options['sorts']['expenditure']['field'] = 'expenditure';
  $handler->display->display_options['sorts']['expenditure']['group_type'] = 'sum';
  $handler->display->display_options['sorts']['expenditure']['order'] = 'DESC';
  $translatables['user_rankings'] = array(
    t('Master'),
    t('Most trades'),
    t('more'),
    t('Apply'),
    t('Reset'),
    t('Sort by'),
    t('Asc'),
    t('Desc'),
    t('Items per page'),
    t('- All -'),
    t('Offset'),
    t('All'),
    t('Name'),
    t('Promiscuity'),
    t('Volume'),
    t('Trades'),
    t('Number of trades'),
    t('Incomes'),
    t('incomes'),
    t('Income'),
    t('Expenditure'),
    t('expenditures'),
  );

  return $view;
}

function mcapi_getview_balance_history() {
  $view = new view;
  $view->name = 'balance_history';
  $view->description = 'Unixtimes and running balances';
  $view->tag = 'mcapi';
  $view->base_table = 'mcapi_index';
  $view->human_name = 'Balance history';
  $view->core = 7;
  $view->api_version = '3.0';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Master */
  $handler = $view->new_display('default', 'Master', 'default');
  $handler->display->display_options['title'] = 'Balance history';
  $handler->display->display_options['access']['type'] = 'none';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['query']['options']['query_comment'] = FALSE;
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'full';
  $handler->display->display_options['pager']['options']['items_per_page'] = '';
  $handler->display->display_options['style_plugin'] = 'table';
  $handler->display->display_options['style_options']['grouping'] = 'currcode';
  $handler->display->display_options['style_options']['columns'] = array(
    'balance' => 'balance',
    'created' => 'created',
    'currcode' => 'currcode',
  );
  $handler->display->display_options['style_options']['default'] = '-1';
  $handler->display->display_options['style_options']['info'] = array(
    'balance' => array(
      'align' => '',
      'separator' => '',
    ),
    'created' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
    ),
    'currcode' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
    ),
  );
  $handler->display->display_options['style_options']['override'] = 1;
  $handler->display->display_options['style_options']['sticky'] = 0;
  $handler->display->display_options['style_options']['empty_table'] = 0;
  /* Relationship: Transaction index: 1st user */
  $handler->display->display_options['relationships']['uid1']['id'] = 'uid1';
  $handler->display->display_options['relationships']['uid1']['table'] = 'mcapi_index';
  $handler->display->display_options['relationships']['uid1']['field'] = 'uid1';
  $handler->display->display_options['relationships']['uid1']['required'] = 1;
  /* Field: Transaction index: Balance */
  $handler->display->display_options['fields']['balance']['id'] = 'balance';
  $handler->display->display_options['fields']['balance']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['balance']['field'] = 'balance';
  $handler->display->display_options['fields']['balance']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['balance']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['balance']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['balance']['alter']['external'] = 0;
  $handler->display->display_options['fields']['balance']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['balance']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['balance']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['balance']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['balance']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['balance']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['balance']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['balance']['alter']['html'] = 0;
  $handler->display->display_options['fields']['balance']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['balance']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['balance']['hide_empty'] = 0;
  $handler->display->display_options['fields']['balance']['empty_zero'] = 0;
  /* Field: Transaction index: Created */
  $handler->display->display_options['fields']['created']['id'] = 'created';
  $handler->display->display_options['fields']['created']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['created']['field'] = 'created';
  $handler->display->display_options['fields']['created']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['created']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['created']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['created']['alter']['external'] = 0;
  $handler->display->display_options['fields']['created']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['created']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['created']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['created']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['created']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['created']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['created']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['created']['alter']['html'] = 0;
  $handler->display->display_options['fields']['created']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['created']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['created']['hide_empty'] = 0;
  $handler->display->display_options['fields']['created']['empty_zero'] = 0;
  $handler->display->display_options['fields']['created']['date_format'] = 'long';
  /* Contextual filter: User: Uid */
  $handler->display->display_options['arguments']['uid']['id'] = 'uid';
  $handler->display->display_options['arguments']['uid']['table'] = 'users';
  $handler->display->display_options['arguments']['uid']['field'] = 'uid';
  $handler->display->display_options['arguments']['uid']['relationship'] = 'uid1';
  $handler->display->display_options['arguments']['uid']['default_action'] = 'default';
  $handler->display->display_options['arguments']['uid']['default_argument_type'] = 'user';
  $handler->display->display_options['arguments']['uid']['default_argument_options']['user'] = FALSE;
  $handler->display->display_options['arguments']['uid']['default_argument_skip_url'] = 0;
  $handler->display->display_options['arguments']['uid']['summary']['number_of_records'] = '0';
  $handler->display->display_options['arguments']['uid']['summary']['format'] = 'default_summary';
  $handler->display->display_options['arguments']['uid']['summary_options']['items_per_page'] = '25';
  $handler->display->display_options['arguments']['uid']['break_phrase'] = 0;
  $handler->display->display_options['arguments']['uid']['not'] = 0;
  /* Contextual filter: Transaction index: Currency */
  $handler->display->display_options['arguments']['currcode']['id'] = 'currcode';
  $handler->display->display_options['arguments']['currcode']['table'] = 'mcapi_index';
  $handler->display->display_options['arguments']['currcode']['field'] = 'currcode';
  $handler->display->display_options['arguments']['currcode']['default_action'] = 'default';
  $handler->display->display_options['arguments']['currcode']['default_argument_type'] = 'php';
  $handler->display->display_options['arguments']['currcode']['default_argument_options']['code'] = 'return array_shift(array_keys(currencies_load()));';
  $handler->display->display_options['arguments']['currcode']['default_argument_skip_url'] = 0;
  $handler->display->display_options['arguments']['currcode']['summary']['number_of_records'] = '0';
  $handler->display->display_options['arguments']['currcode']['summary']['format'] = 'default_summary';
  $handler->display->display_options['arguments']['currcode']['summary_options']['items_per_page'] = '25';

  /* Display: Block */
  $handler = $view->new_display('block', 'Block', 'block');
  $handler->display->display_options['block_description'] = 'Running balance';
  $translatables['balance_history'] = array(
    t('Master'),
    t('Balance history'),
    t('more'),
    t('Apply'),
    t('Reset'),
    t('Sort by'),
    t('Asc'),
    t('Desc'),
    t('Items per page'),
    t('- All -'),
    t('Offset'),
    t('Balance'),
    t('Created'),
    t('All'),
    t('Block'),
  );
  return $view;
}

function mcapi_getview_transactions_all() {
  $view = new view;
  $view->name = 'transactions_all';
  $view->description = 'For admin or accountant to manage transactions';
  $view->tag = 'mcapi';
  $view->base_table = 'mcapi_transactions';
  $view->human_name = 'Administer transactions';
  $view->core = 7;
  $view->api_version = '3.0';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Master */
  $handler = $view->new_display('default', 'Master', 'default');
  $handler->display->display_options['title'] = 'Transaction management';
  $handler->display->display_options['access']['type'] = 'perm';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['query']['options']['query_comment'] = FALSE;
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'full';
  $handler->display->display_options['pager']['options']['items_per_page'] = '50';
  $handler->display->display_options['style_plugin'] = 'table';
  $handler->display->display_options['style_options']['columns'] = array(
    'created' => 'created',
    'payee' => 'payee',
    'payer' => 'payer',
    'type' => 'type',
    'state' => 'state',
    'worth' => 'worth',
    'field_cat' => 'field_cat',
  );
  $handler->display->display_options['style_options']['default'] = 'created';
  $handler->display->display_options['style_options']['info'] = array(
    'participant' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
    ),
    'payee' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
    ),
    'payer' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
    ),
    'created' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
    ),
    'type' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
    ),
    'state' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
    ),
    'worth' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
    ),
  );
  $handler->display->display_options['style_options']['override'] = 1;
  $handler->display->display_options['style_options']['sticky'] = 1;
  $handler->display->display_options['style_options']['order'] = 'desc';
  $handler->display->display_options['style_options']['summary'] = 'All transactions, filtered';
  /* Header: Global: Text area */
  $handler->display->display_options['header']['area']['id'] = 'area';
  $handler->display->display_options['header']['area']['table'] = 'views';
  $handler->display->display_options['header']['area']['field'] = 'area';
  $handler->display->display_options['header']['area']['empty'] = FALSE;
  $handler->display->display_options['header']['area']['content'] = 'All transactions can be seen from here. Use the filters to list what you need.';
  /* Relationship: Transaction: Payee */
  $handler->display->display_options['relationships']['payee']['id'] = 'payee';
  $handler->display->display_options['relationships']['payee']['table'] = 'mcapi_transactions';
  $handler->display->display_options['relationships']['payee']['field'] = 'payee';
  $handler->display->display_options['relationships']['payee']['required'] = 1;
  /* Relationship: Transaction: Payer */
  $handler->display->display_options['relationships']['payer']['id'] = 'payer';
  $handler->display->display_options['relationships']['payer']['table'] = 'mcapi_transactions';
  $handler->display->display_options['relationships']['payer']['field'] = 'payer';
  $handler->display->display_options['relationships']['payer']['required'] = 1;
  /* Field: User: Name */
  $handler->display->display_options['fields']['name']['id'] = 'name';
  $handler->display->display_options['fields']['name']['table'] = 'users';
  $handler->display->display_options['fields']['name']['field'] = 'name';
  $handler->display->display_options['fields']['name']['relationship'] = 'payee';
  $handler->display->display_options['fields']['name']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['name']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['name']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['name']['alter']['external'] = 0;
  $handler->display->display_options['fields']['name']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['name']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['name']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['name']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['name']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['name']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['name']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['name']['alter']['html'] = 0;
  $handler->display->display_options['fields']['name']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['name']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['name']['hide_empty'] = 0;
  $handler->display->display_options['fields']['name']['empty_zero'] = 0;
  $handler->display->display_options['fields']['name']['hide_alter_empty'] = 0;
  $handler->display->display_options['fields']['name']['link_to_user'] = 1;
  $handler->display->display_options['fields']['name']['overwrite_anonymous'] = 0;
  $handler->display->display_options['fields']['name']['format_username'] = 1;
  /* Field: User: Name */
  $handler->display->display_options['fields']['name_1']['id'] = 'name_1';
  $handler->display->display_options['fields']['name_1']['table'] = 'users';
  $handler->display->display_options['fields']['name_1']['field'] = 'name';
  $handler->display->display_options['fields']['name_1']['relationship'] = 'payer';
  $handler->display->display_options['fields']['name_1']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['name_1']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['name_1']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['name_1']['alter']['external'] = 0;
  $handler->display->display_options['fields']['name_1']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['name_1']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['name_1']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['name_1']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['name_1']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['name_1']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['name_1']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['name_1']['alter']['html'] = 0;
  $handler->display->display_options['fields']['name_1']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['name_1']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['name_1']['hide_empty'] = 0;
  $handler->display->display_options['fields']['name_1']['empty_zero'] = 0;
  $handler->display->display_options['fields']['name_1']['hide_alter_empty'] = 0;
  $handler->display->display_options['fields']['name_1']['link_to_user'] = 1;
  $handler->display->display_options['fields']['name_1']['overwrite_anonymous'] = 0;
  $handler->display->display_options['fields']['name_1']['format_username'] = 1;
  /* Field: Transaction: Type */
  $handler->display->display_options['fields']['type']['id'] = 'type';
  $handler->display->display_options['fields']['type']['table'] = 'mcapi_transactions';
  $handler->display->display_options['fields']['type']['field'] = 'type';
  $handler->display->display_options['fields']['type']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['type']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['type']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['type']['alter']['external'] = 0;
  $handler->display->display_options['fields']['type']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['type']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['type']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['type']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['type']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['type']['alter']['html'] = 0;
  $handler->display->display_options['fields']['type']['element_label_colon'] = 0;
  $handler->display->display_options['fields']['type']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['type']['hide_empty'] = 0;
  $handler->display->display_options['fields']['type']['empty_zero'] = 0;
  /* Field: Transaction: State */
  $handler->display->display_options['fields']['state']['id'] = 'state';
  $handler->display->display_options['fields']['state']['table'] = 'mcapi_transactions';
  $handler->display->display_options['fields']['state']['field'] = 'state';
  $handler->display->display_options['fields']['state']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['state']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['state']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['state']['alter']['external'] = 0;
  $handler->display->display_options['fields']['state']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['state']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['state']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['state']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['state']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['state']['alter']['html'] = 0;
  $handler->display->display_options['fields']['state']['element_label_colon'] = 0;
  $handler->display->display_options['fields']['state']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['state']['hide_empty'] = 0;
  $handler->display->display_options['fields']['state']['empty_zero'] = 0;
  /* Field: Transaction: Link */
  $handler->display->display_options['fields']['view_transaction']['id'] = 'view_transaction';
  $handler->display->display_options['fields']['view_transaction']['table'] = 'mcapi_transactions';
  $handler->display->display_options['fields']['view_transaction']['field'] = 'view_transaction';
  $handler->display->display_options['fields']['view_transaction']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['view_transaction']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['view_transaction']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['view_transaction']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['view_transaction']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['view_transaction']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['view_transaction']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['view_transaction']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['view_transaction']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['view_transaction']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['view_transaction']['alter']['html'] = 0;
  $handler->display->display_options['fields']['view_transaction']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['view_transaction']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['view_transaction']['hide_empty'] = 0;
  $handler->display->display_options['fields']['view_transaction']['empty_zero'] = 0;
  $handler->display->display_options['fields']['view_transaction']['hide_alter_empty'] = 0;
  $handler->display->display_options['fields']['view_transaction']['text'] = 'view';
  /* Field: Transaction: Edit link */
  $handler->display->display_options['fields']['edit_transaction']['id'] = 'edit_transaction';
  $handler->display->display_options['fields']['edit_transaction']['table'] = 'mcapi_transactions';
  $handler->display->display_options['fields']['edit_transaction']['field'] = 'edit_transaction';
  $handler->display->display_options['fields']['edit_transaction']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['edit_transaction']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['edit_transaction']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['edit_transaction']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['edit_transaction']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['edit_transaction']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['edit_transaction']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['edit_transaction']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['edit_transaction']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['edit_transaction']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['edit_transaction']['alter']['html'] = 0;
  $handler->display->display_options['fields']['edit_transaction']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['edit_transaction']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['edit_transaction']['hide_empty'] = 0;
  $handler->display->display_options['fields']['edit_transaction']['empty_zero'] = 0;
  $handler->display->display_options['fields']['edit_transaction']['hide_alter_empty'] = 0;
  $handler->display->display_options['fields']['edit_transaction']['text'] = 'edit this';
  /* Sort criterion: Transaction: created */
  $handler->display->display_options['sorts']['created']['id'] = 'created';
  $handler->display->display_options['sorts']['created']['table'] = 'mcapi_transactions';
  $handler->display->display_options['sorts']['created']['field'] = 'created';
  $handler->display->display_options['sorts']['created']['order'] = 'DESC';
  $handler->display->display_options['sorts']['created']['granularity'] = 'minute';
  /* Filter criterion: Transaction: Type */
  $handler->display->display_options['filters']['type']['id'] = 'type';
  $handler->display->display_options['filters']['type']['table'] = 'mcapi_transactions';
  $handler->display->display_options['filters']['type']['field'] = 'type';
  $handler->display->display_options['filters']['type']['group'] = 0;
  $handler->display->display_options['filters']['type']['exposed'] = TRUE;
  $handler->display->display_options['filters']['type']['expose']['operator_id'] = 'type_op';
  $handler->display->display_options['filters']['type']['expose']['label'] = 'Type';
  $handler->display->display_options['filters']['type']['expose']['operator'] = 'type_op';
  $handler->display->display_options['filters']['type']['expose']['identifier'] = 'type';
  $handler->display->display_options['filters']['type']['expose']['multiple'] = 1;
  $handler->display->display_options['filters']['type']['expose']['reduce'] = 0;
  $handler->display->display_options['filters']['type']['reduce_duplicates'] = 0;
  /* Filter criterion: User: Name */
  $handler->display->display_options['filters']['uid']['id'] = 'uid';
  $handler->display->display_options['filters']['uid']['table'] = 'users';
  $handler->display->display_options['filters']['uid']['field'] = 'uid';
  $handler->display->display_options['filters']['uid']['relationship'] = 'payee';
  $handler->display->display_options['filters']['uid']['value'] = '';
  $handler->display->display_options['filters']['uid']['group'] = 0;
  $handler->display->display_options['filters']['uid']['exposed'] = TRUE;
  $handler->display->display_options['filters']['uid']['expose']['operator_id'] = 'uid_op';
  $handler->display->display_options['filters']['uid']['expose']['label'] = 'Name';
  $handler->display->display_options['filters']['uid']['expose']['operator'] = 'uid_op';
  $handler->display->display_options['filters']['uid']['expose']['identifier'] = 'uid';
  $handler->display->display_options['filters']['uid']['expose']['multiple'] = FALSE;
  $handler->display->display_options['filters']['uid']['expose']['reduce'] = 0;
  /* Filter criterion: User: Name */
  $handler->display->display_options['filters']['uid_1']['id'] = 'uid_1';
  $handler->display->display_options['filters']['uid_1']['table'] = 'users';
  $handler->display->display_options['filters']['uid_1']['field'] = 'uid';
  $handler->display->display_options['filters']['uid_1']['relationship'] = 'payer';
  $handler->display->display_options['filters']['uid_1']['value'] = '';
  $handler->display->display_options['filters']['uid_1']['exposed'] = TRUE;
  $handler->display->display_options['filters']['uid_1']['expose']['operator_id'] = 'uid_1_op';
  $handler->display->display_options['filters']['uid_1']['expose']['label'] = 'Name';
  $handler->display->display_options['filters']['uid_1']['expose']['operator'] = 'uid_1_op';
  $handler->display->display_options['filters']['uid_1']['expose']['identifier'] = 'uid_1';
  $handler->display->display_options['filters']['uid_1']['expose']['multiple'] = FALSE;
  $handler->display->display_options['filters']['uid_1']['expose']['reduce'] = 0;
  /* Filter criterion: Transaction: State */
  $handler->display->display_options['filters']['state']['id'] = 'state';
  $handler->display->display_options['filters']['state']['table'] = 'mcapi_transactions';
  $handler->display->display_options['filters']['state']['field'] = 'state';
  $handler->display->display_options['filters']['state']['value'] = array(
    0 => '0',
  );
  $handler->display->display_options['filters']['state']['exposed'] = TRUE;
  $handler->display->display_options['filters']['state']['expose']['operator_id'] = '';
  $handler->display->display_options['filters']['state']['expose']['label'] = 'State';
  $handler->display->display_options['filters']['state']['expose']['operator'] = 'state_op';
  $handler->display->display_options['filters']['state']['expose']['identifier'] = 'state';
  $handler->display->display_options['filters']['state']['expose']['required'] = 1;
  $handler->display->display_options['filters']['state']['expose']['reduce'] = 0;
  /* Filter criterion: Transaction: Value (worth:quantity) */
  $handler->display->display_options['filters']['worth_quantity']['id'] = 'worth_quantity';
  $handler->display->display_options['filters']['worth_quantity']['table'] = 'field_data_worth';
  $handler->display->display_options['filters']['worth_quantity']['field'] = 'worth_quantity';
  $handler->display->display_options['filters']['worth_quantity']['exposed'] = TRUE;
  $handler->display->display_options['filters']['worth_quantity']['expose']['operator_id'] = 'worth_quantity_op';
  $handler->display->display_options['filters']['worth_quantity']['expose']['label'] = 'Quantity';
  $handler->display->display_options['filters']['worth_quantity']['expose']['use_operator'] = 1;
  $handler->display->display_options['filters']['worth_quantity']['expose']['operator'] = 'worth_quantity_op';
  $handler->display->display_options['filters']['worth_quantity']['expose']['identifier'] = 'worth_quantity';
  $handler->display->display_options['filters']['worth_quantity']['expose']['multiple'] = FALSE;
  /* Filter criterion: Transaction: Value (worth:currcode) */
  $handler->display->display_options['filters']['worth_currcode']['id'] = 'worth_currcode';
  $handler->display->display_options['filters']['worth_currcode']['table'] = 'field_data_worth';
  $handler->display->display_options['filters']['worth_currcode']['field'] = 'worth_currcode';
  $handler->display->display_options['filters']['worth_currcode']['exposed'] = TRUE;
  $handler->display->display_options['filters']['worth_currcode']['expose']['operator_id'] = '';
  $handler->display->display_options['filters']['worth_currcode']['expose']['label'] = 'Currency';
  $handler->display->display_options['filters']['worth_currcode']['expose']['operator'] = 'worth_currcode_op';
  $handler->display->display_options['filters']['worth_currcode']['expose']['identifier'] = 'worth_currcode';
  $handler->display->display_options['filters']['worth_currcode']['expose']['reduce'] = 0;

  /* Display: Page */
  $handler = $view->new_display('page', 'Page', 'page');
  $handler->display->display_options['path'] = 'admin/accounting/transactions';
  $handler->display->display_options['menu']['type'] = 'normal';
  $handler->display->display_options['menu']['title'] = 'Transactions';
  $handler->display->display_options['menu']['description'] = 'Filter through all the transactions on the system';
  $handler->display->display_options['menu']['weight'] = '0';
  $handler->display->display_options['menu']['name'] = 'management';
  $translatables['transactions_all'] = array(
    t('Master'),
    t('Transaction management'),
    t('more'),
    t('Apply'),
    t('Reset'),
    t('Sort by'),
    t('Asc'),
    t('Desc'),
    t('Items per page'),
    t('- All -'),
    t('Offset'),
    t('All transactions can be seen from here. Use the filters to list what you need.'),
    t('Payee'),
    t('Payer'),
    t('Name'),
    t('Type'),
    t('State'),
    t('Link'),
    t('view'),
    t('Edit link'),
    t('edit this'),
    t('Quantity'),
    t('Currency'),
    t('Page'),
  );

  return $view;
}

function mcapi_getview_user_transactions_index() {
  $view = new view;
  $view->name = 'user_transactions_index';
  $view->description = 'statement of one user, with otheruser field and running balance (reads transaction index table)';
  $view->tag = 'default';
  $view->base_table = 'mcapi_index';
  $view->human_name = 'User transactions';
  $view->core = 7;
  $view->api_version = '3.0';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Master */
  $handler = $view->new_display('default', 'Master', 'default');
  $handler->display->display_options['title'] = 'User transactions';
  $handler->display->display_options['access']['type'] = 'none';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['query']['options']['query_comment'] = FALSE;
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'full';
  $handler->display->display_options['pager']['options']['items_per_page'] = '25';
  $handler->display->display_options['style_plugin'] = 'table';
  /* Field: Transaction index: Created */
  $handler->display->display_options['fields']['created']['id'] = 'created';
  $handler->display->display_options['fields']['created']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['created']['field'] = 'created';
  $handler->display->display_options['fields']['created']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['created']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['created']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['created']['alter']['external'] = 0;
  $handler->display->display_options['fields']['created']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['created']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['created']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['created']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['created']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['created']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['created']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['created']['alter']['html'] = 0;
  $handler->display->display_options['fields']['created']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['created']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['created']['hide_empty'] = 0;
  $handler->display->display_options['fields']['created']['empty_zero'] = 0;
  $handler->display->display_options['fields']['created']['hide_alter_empty'] = 0;
  $handler->display->display_options['fields']['created']['date_format'] = 'long';
  /* Field: Transaction index: 2nd Person */
  $handler->display->display_options['fields']['uid2']['id'] = 'uid2';
  $handler->display->display_options['fields']['uid2']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['uid2']['field'] = 'uid2';
  $handler->display->display_options['fields']['uid2']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['uid2']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['uid2']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['uid2']['alter']['external'] = 0;
  $handler->display->display_options['fields']['uid2']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['uid2']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['uid2']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['uid2']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['uid2']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['uid2']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['uid2']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['uid2']['alter']['html'] = 0;
  $handler->display->display_options['fields']['uid2']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['uid2']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['uid2']['hide_empty'] = 0;
  $handler->display->display_options['fields']['uid2']['empty_zero'] = 0;
  $handler->display->display_options['fields']['uid2']['hide_alter_empty'] = 0;
  $handler->display->display_options['fields']['uid2']['link_to_user'] = 1;
  /* Field: Transaction index: Value */
  $handler->display->display_options['fields']['diff']['id'] = 'diff';
  $handler->display->display_options['fields']['diff']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['diff']['field'] = 'diff';
  $handler->display->display_options['fields']['diff']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['diff']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['diff']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['diff']['alter']['external'] = 0;
  $handler->display->display_options['fields']['diff']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['diff']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['diff']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['diff']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['diff']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['diff']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['diff']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['diff']['alter']['html'] = 0;
  $handler->display->display_options['fields']['diff']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['diff']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['diff']['hide_empty'] = 0;
  $handler->display->display_options['fields']['diff']['empty_zero'] = 0;
  $handler->display->display_options['fields']['diff']['hide_alter_empty'] = 0;
  /* Field: Transaction index: Balance */
  $handler->display->display_options['fields']['balance']['id'] = 'balance';
  $handler->display->display_options['fields']['balance']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['balance']['field'] = 'balance';
  $handler->display->display_options['fields']['balance']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['balance']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['balance']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['balance']['alter']['external'] = 0;
  $handler->display->display_options['fields']['balance']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['balance']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['balance']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['balance']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['balance']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['balance']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['balance']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['balance']['alter']['html'] = 0;
  $handler->display->display_options['fields']['balance']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['balance']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['balance']['hide_empty'] = 0;
  $handler->display->display_options['fields']['balance']['empty_zero'] = 0;
  $handler->display->display_options['fields']['balance']['hide_alter_empty'] = 0;
  /* Contextual filter: Transaction index: 1st user */
  $handler->display->display_options['arguments']['uid1']['id'] = 'uid1';
  $handler->display->display_options['arguments']['uid1']['table'] = 'mcapi_index';
  $handler->display->display_options['arguments']['uid1']['field'] = 'uid1';
  $handler->display->display_options['arguments']['uid1']['default_action'] = 'default';
  $handler->display->display_options['arguments']['uid1']['default_argument_type'] = 'current_user';
  $handler->display->display_options['arguments']['uid1']['default_argument_skip_url'] = 1;
  $handler->display->display_options['arguments']['uid1']['summary']['number_of_records'] = '0';
  $handler->display->display_options['arguments']['uid1']['summary']['format'] = 'default_summary';
  $handler->display->display_options['arguments']['uid1']['summary_options']['items_per_page'] = '25';
  /* Filter criterion: Transaction index: Currency */
  $handler->display->display_options['filters']['currcode']['id'] = 'currcode';
  $handler->display->display_options['filters']['currcode']['table'] = 'mcapi_index';
  $handler->display->display_options['filters']['currcode']['field'] = 'currcode';
  $handler->display->display_options['filters']['currcode']['exposed'] = TRUE;
  $handler->display->display_options['filters']['currcode']['expose']['operator_id'] = '';
  $handler->display->display_options['filters']['currcode']['expose']['label'] = 'Currency';
  $handler->display->display_options['filters']['currcode']['expose']['operator'] = 'currcode_op';
  $handler->display->display_options['filters']['currcode']['expose']['identifier'] = 'currcode';
  $handler->display->display_options['filters']['currcode']['expose']['reduce'] = 0;

  /* Display: Page */
  $handler = $view->new_display('page', 'Page', 'page');
  $handler->display->display_options['path'] = 'user/%/statement';
  $handler->display->display_options['menu']['type'] = 'tab';
  $handler->display->display_options['menu']['title'] = 'Trades';
  $handler->display->display_options['menu']['weight'] = '0';
  $translatables['user_transactions'] = array(
    t('Master'),
    t('User transactions'),
    t('more'),
    t('Apply'),
    t('Reset'),
    t('Sort by'),
    t('Asc'),
    t('Desc'),
    t('Items per page'),
    t('- All -'),
    t('Offset'),
    t('Created'),
    t('2nd Person'),
    t('Value'),
    t('Balance'),
    t('All'),
    t('Currency'),
    t('Page'),
  );

}

function mcapi_getview_user_trade() {

  $view = new view;
  $view->name = 'user_trade';
  $view->description = 'income and expenditure in separate tables';
  $view->tag = 'default';
  $view->base_table = 'mcapi_index';
  $view->human_name = 'Trade';
  $view->core = 7;
  $view->api_version = '3.0';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Master */
  $handler = $view->new_display('default', 'Master', 'default');
  $handler->display->display_options['title'] = 'Expenditure';
  $handler->display->display_options['access']['type'] = 'none';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['query']['options']['query_comment'] = FALSE;
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'some';
  $handler->display->display_options['pager']['options']['items_per_page'] = '10';
  $handler->display->display_options['style_plugin'] = 'table';
  $handler->display->display_options['style_options']['columns'] = array(
    'currcode' => 'currcode',
    'created' => 'created',
    'expenditure' => 'expenditure',
    'name' => 'name',
  );
  $handler->display->display_options['style_options']['default'] = '-1';
  $handler->display->display_options['style_options']['info'] = array(
    'currcode' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
    ),
    'created' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
    ),
    'expenditure' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
    ),
    'name' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
    ),
  );
  $handler->display->display_options['style_options']['override'] = 1;
  $handler->display->display_options['style_options']['sticky'] = 0;
  $handler->display->display_options['style_options']['empty_table'] = 0;
  /* Relationship: Transaction index: 1st user */
  $handler->display->display_options['relationships']['uid1']['id'] = 'uid1';
  $handler->display->display_options['relationships']['uid1']['table'] = 'mcapi_index';
  $handler->display->display_options['relationships']['uid1']['field'] = 'uid1';
  $handler->display->display_options['relationships']['uid1']['required'] = 1;
  /* Relationship: Transaction index: 2nd user */
  $handler->display->display_options['relationships']['uid2']['id'] = 'uid2';
  $handler->display->display_options['relationships']['uid2']['table'] = 'mcapi_index';
  $handler->display->display_options['relationships']['uid2']['field'] = 'uid2';
  $handler->display->display_options['relationships']['uid2']['required'] = 1;
  /* Field: Transaction index: Currency */
  $handler->display->display_options['fields']['currcode']['id'] = 'currcode';
  $handler->display->display_options['fields']['currcode']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['currcode']['field'] = 'currcode';
  /* Field: Transaction index: Created moment */
  $handler->display->display_options['fields']['created']['id'] = 'created';
  $handler->display->display_options['fields']['created']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['created']['field'] = 'created';
  $handler->display->display_options['fields']['created']['label'] = 'Created';
  $handler->display->display_options['fields']['created']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['created']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['created']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['created']['alter']['external'] = 0;
  $handler->display->display_options['fields']['created']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['created']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['created']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['created']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['created']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['created']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['created']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['created']['alter']['html'] = 0;
  $handler->display->display_options['fields']['created']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['created']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['created']['hide_empty'] = 0;
  $handler->display->display_options['fields']['created']['empty_zero'] = 0;
  $handler->display->display_options['fields']['created']['hide_alter_empty'] = 0;
  $handler->display->display_options['fields']['created']['date_format'] = 'long';
  /* Field: User: Name */
  $handler->display->display_options['fields']['name']['id'] = 'name';
  $handler->display->display_options['fields']['name']['table'] = 'users';
  $handler->display->display_options['fields']['name']['field'] = 'name';
  $handler->display->display_options['fields']['name']['relationship'] = 'uid1';
  $handler->display->display_options['fields']['name']['label'] = '';
  $handler->display->display_options['fields']['name']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['name']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['name']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['name']['alter']['external'] = 0;
  $handler->display->display_options['fields']['name']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['name']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['name']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['name']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['name']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['name']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['name']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['name']['alter']['html'] = 0;
  $handler->display->display_options['fields']['name']['element_label_colon'] = FALSE;
  $handler->display->display_options['fields']['name']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['name']['hide_empty'] = 0;
  $handler->display->display_options['fields']['name']['empty_zero'] = 0;
  $handler->display->display_options['fields']['name']['hide_alter_empty'] = 0;
  $handler->display->display_options['fields']['name']['link_to_user'] = 1;
  $handler->display->display_options['fields']['name']['overwrite_anonymous'] = 0;
  $handler->display->display_options['fields']['name']['format_username'] = 1;
  /* Field: Transaction index: Value */
  $handler->display->display_options['fields']['diff']['id'] = 'diff';
  $handler->display->display_options['fields']['diff']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['diff']['field'] = 'diff';
  $handler->display->display_options['fields']['diff']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['diff']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['diff']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['diff']['alter']['external'] = 0;
  $handler->display->display_options['fields']['diff']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['diff']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['diff']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['diff']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['diff']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['diff']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['diff']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['diff']['alter']['html'] = 0;
  $handler->display->display_options['fields']['diff']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['diff']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['diff']['hide_empty'] = 0;
  $handler->display->display_options['fields']['diff']['empty_zero'] = 0;
  $handler->display->display_options['fields']['diff']['hide_alter_empty'] = 0;
  /* Sort criterion: Transaction index: Created moment */
  $handler->display->display_options['sorts']['created']['id'] = 'created';
  $handler->display->display_options['sorts']['created']['table'] = 'mcapi_index';
  $handler->display->display_options['sorts']['created']['field'] = 'created';
  $handler->display->display_options['sorts']['created']['order'] = 'DESC';
  /* Contextual filter: User: Uid */
  $handler->display->display_options['arguments']['uid']['id'] = 'uid';
  $handler->display->display_options['arguments']['uid']['table'] = 'users';
  $handler->display->display_options['arguments']['uid']['field'] = 'uid';
  $handler->display->display_options['arguments']['uid']['relationship'] = 'uid1';
  $handler->display->display_options['arguments']['uid']['default_action'] = 'not found';
  $handler->display->display_options['arguments']['uid']['default_argument_type'] = 'fixed';
  $handler->display->display_options['arguments']['uid']['default_argument_skip_url'] = 0;
  $handler->display->display_options['arguments']['uid']['summary']['number_of_records'] = '0';
  $handler->display->display_options['arguments']['uid']['summary']['format'] = 'default_summary';
  $handler->display->display_options['arguments']['uid']['summary_options']['items_per_page'] = '25';
  $handler->display->display_options['arguments']['uid']['break_phrase'] = 0;
  $handler->display->display_options['arguments']['uid']['not'] = 0;
  /* Filter criterion: Transaction index: Currency */
  $handler->display->display_options['filters']['currcode']['id'] = 'currcode';
  $handler->display->display_options['filters']['currcode']['table'] = 'mcapi_index';
  $handler->display->display_options['filters']['currcode']['field'] = 'currcode';
  $handler->display->display_options['filters']['currcode']['value'] = array(
    'andyhour' => 'andyhour',
  );
  $handler->display->display_options['filters']['currcode']['exposed'] = TRUE;
  $handler->display->display_options['filters']['currcode']['expose']['operator_id'] = '';
  $handler->display->display_options['filters']['currcode']['expose']['label'] = 'Currency';
  $handler->display->display_options['filters']['currcode']['expose']['operator'] = 'currcode_op';
  $handler->display->display_options['filters']['currcode']['expose']['identifier'] = 'currcode';
  $handler->display->display_options['filters']['currcode']['expose']['required'] = 1;
  $handler->display->display_options['filters']['currcode']['expose']['reduce'] = 0;
  /* Filter criterion: Transaction index: Value */
  $handler->display->display_options['filters']['diff']['id'] = 'diff';
  $handler->display->display_options['filters']['diff']['table'] = 'mcapi_index';
  $handler->display->display_options['filters']['diff']['field'] = 'diff';
  $handler->display->display_options['filters']['diff']['operator'] = '>';
  $handler->display->display_options['filters']['diff']['value']['value'] = '0';

  /* Display: Page */
  $handler = $view->new_display('page', 'Page', 'page');
  $handler->display->display_options['path'] = 'user/%/trade';
  $handler->display->display_options['menu']['type'] = 'tab';
  $handler->display->display_options['menu']['title'] = 'Transactions';
  $handler->display->display_options['menu']['description'] = 'Income / Expenditure';
  $handler->display->display_options['menu']['weight'] = '0';
  $handler->display->display_options['menu']['name'] = 'management';

  /* Display: Attachment */
  $handler = $view->new_display('attachment', 'Attachment', 'attachment_1');
  $handler->display->display_options['defaults']['filters'] = FALSE;
  /* Filter criterion: Transaction index: Currency */
  $handler->display->display_options['filters']['currcode']['id'] = 'currcode';
  $handler->display->display_options['filters']['currcode']['table'] = 'mcapi_index';
  $handler->display->display_options['filters']['currcode']['field'] = 'currcode';
  $handler->display->display_options['filters']['currcode']['value'] = array(
    'andyhour' => 'andyhour',
  );
  $handler->display->display_options['filters']['currcode']['exposed'] = TRUE;
  $handler->display->display_options['filters']['currcode']['expose']['operator_id'] = '';
  $handler->display->display_options['filters']['currcode']['expose']['label'] = 'Currency';
  $handler->display->display_options['filters']['currcode']['expose']['operator'] = 'currcode_op';
  $handler->display->display_options['filters']['currcode']['expose']['identifier'] = 'currcode';
  $handler->display->display_options['filters']['currcode']['expose']['required'] = 1;
  $handler->display->display_options['filters']['currcode']['expose']['reduce'] = 0;
  /* Filter criterion: Transaction index: Value */
  $handler->display->display_options['filters']['diff']['id'] = 'diff';
  $handler->display->display_options['filters']['diff']['table'] = 'mcapi_index';
  $handler->display->display_options['filters']['diff']['field'] = 'diff';
  $handler->display->display_options['filters']['diff']['operator'] = '<';
  $handler->display->display_options['filters']['diff']['value']['value'] = '0';
  $handler->display->display_options['path'] = 'user/%/trade';

  $handler->display->display_options['defaults']['title'] = FALSE;
  $handler->display->display_options['title'] = 'Income';
  $handler->display->display_options['pager']['type'] = 'some';
  $handler->display->display_options['displays'] = array(
    'page' => 'page',
    'default' => 0,
    'page_1' => 0,
  );
  $handler->display->display_options['attachment_position'] = 'after';
  return $view;
}