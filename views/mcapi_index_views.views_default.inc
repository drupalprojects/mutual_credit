<?php
//Id;

function mcapi_index_views_views_default_views() {
  currencies_load(); //because this seems to run before mcapi_init()
  $currencies = &drupal_static('currencies');
  $multicurrencies = count($currencies) > 1;
  $viewnames = array('user_transactions_summary', 'user_rankings', 'balance_history', 'system_stats');

  if (module_exists('date_views')) {
    $viewnames[] = 'statement';
  }

  foreach ($viewnames as $viewname) {
    $function  = 'mcapi_getview_'.$viewname;
    $views[$viewname] = $function($multicurrencies);
  }
  return $views;
}

function mcapi_getview_user_transactions_summary($multicurrencies) {
  $view = new view;
  $view->name = 'user_transactions_summaries';
  $view->description = 'per currency transaction summary for individual users';
  $view->tag = 'mcapi (index)';
  $view->base_table = 'mcapi_index';
  $view->human_name = 'User transaction summaries';
  $view->core = 7;
  $view->api_version = '3.0';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Master */
  $handler = $view->new_display('default', 'Master', 'default');
  $handler->display->display_options['group_by'] = TRUE;
  $handler->display->display_options['access']['type'] = 'perm';
  $handler->display->display_options['access']['perm'] = 'transact';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['query']['options']['query_comment'] = FALSE;
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'full';
  $handler->display->display_options['style_plugin'] = 'default';
  $handler->display->display_options['row_plugin'] = 'fields';
  if ($multicurrencies) {
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
  }
  /* Field: SUM(Transaction index: Value) */
  $handler->display->display_options['fields']['diff']['id'] = 'diff';
  $handler->display->display_options['fields']['diff']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['diff']['field'] = 'diff';
  $handler->display->display_options['fields']['diff']['group_type'] = 'sum';
  $handler->display->display_options['fields']['diff']['label'] = 'Balance';
  $handler->display->display_options['fields']['diff']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['diff']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['diff']['hide_empty'] = 0;
  $handler->display->display_options['fields']['diff']['empty_zero'] = 0;
  $handler->display->display_options['fields']['diff']['hide_alter_empty'] = 0;
  /* Field: COUNT(User: Uid) in fact this could be any field because we are just counting result rows */
  $handler->display->display_options['fields']['uid_1']['id'] = 'uid_1';
  $handler->display->display_options['fields']['uid_1']['table'] = 'users';
  $handler->display->display_options['fields']['uid_1']['field'] = 'uid1';
  $handler->display->display_options['fields']['uid_1']['group_type'] = 'count';
  $handler->display->display_options['fields']['uid_1']['label'] = 'Trades';
  $handler->display->display_options['fields']['uid_1']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['uid_1']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['uid_1']['hide_empty'] = 0;
  $handler->display->display_options['fields']['uid_1']['empty_zero'] = 0;
  $handler->display->display_options['fields']['uid_1']['hide_alter_empty'] = 0;
  $handler->display->display_options['fields']['uid_1']['format_plural'] = 0;
  /* Field: SUM(Transaction index: Expenditure) */
  $handler->display->display_options['fields']['expenditure']['id'] = 'expenditure';
  $handler->display->display_options['fields']['expenditure']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['expenditure']['field'] = 'expenditure';
  $handler->display->display_options['fields']['expenditure']['group_type'] = 'sum';
  $handler->display->display_options['fields']['expenditure']['label'] = 'Gross expenditure';
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
  $handler->display->display_options['fields']['income']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['income']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['income']['hide_empty'] = 0;
  $handler->display->display_options['fields']['income']['empty_zero'] = 0;
  /* Field: COUNT(DISTINCT Transaction index: 2nd user) */
  $handler->display->display_options['fields']['uid2']['id'] = 'uid2';
  $handler->display->display_options['fields']['uid2']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['uid2']['field'] = 'uid2';
  $handler->display->display_options['fields']['uid2']['group_type'] = 'count_distinct';
  $handler->display->display_options['fields']['uid2']['label'] = 'Promiscuity';
  $handler->display->display_options['fields']['uid2']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['uid2']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['uid2']['hide_empty'] = 0;
  $handler->display->display_options['fields']['uid2']['empty_zero'] = 0;
  $handler->display->display_options['fields']['uid2']['hide_alter_empty'] = 1;
  $handler->display->display_options['fields']['uid2']['format_plural'] = 0;
  /* Contextual filter: Transaction index: 1st user */
  $handler->display->display_options['arguments']['uid1']['id'] = 'uid1';
  $handler->display->display_options['arguments']['uid1']['table'] = 'mcapi_index';
  $handler->display->display_options['arguments']['uid1']['field'] = 'uid1';
  $handler->display->display_options['arguments']['uid1']['default_argument_type'] = 'fixed';
  $handler->display->display_options['arguments']['uid1']['default_argument_skip_url'] = 0;
  $handler->display->display_options['arguments']['uid1']['summary']['number_of_records'] = '0';
  $handler->display->display_options['arguments']['uid1']['summary']['format'] = 'default_summary';
  $handler->display->display_options['arguments']['uid1']['summary_options']['items_per_page'] = '25';

  /* Display: Block */
  $handler = $view->new_display('block', 'Block', 'user_summ_per_curr');
  $handler->display->display_options['block_description'] = 'User transactions summary per currency';

  $translatables['user_transactions_summaries'] = array(
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

function mcapi_getview_statement($multicurrencies) {
  $view = new view();
  $view->name = 'statement';
  $view->description = 'shows trades of one user, with otheruser field and running balance. (not working because of apparent views error)';
  $view->tag = 'mcapi (index)';
  $view->base_table = 'mcapi_index';
  $view->human_name = 'Statement';
  $view->core = 7;
  $view->api_version = '3.0';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Master */
  $handler = $view->new_display('default', 'Master', 'default');
  $handler->display->display_options['title'] = 'User transactions';
  $handler->display->display_options['use_more_always'] = FALSE;
  $handler->display->display_options['access']['type'] = 'perm';
  $handler->display->display_options['access']['perm'] = 'transact';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['query']['options']['query_comment'] = FALSE;
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'date_views_pager';
  $handler->display->display_options['pager']['options']['pager_position'] = 'bottom';
  $handler->display->display_options['style_plugin'] = 'table';
  /* No results behavior: Global: Text area */
  $handler->display->display_options['empty']['area']['id'] = 'area';
  $handler->display->display_options['empty']['area']['table'] = 'views';
  $handler->display->display_options['empty']['area']['field'] = 'area';
  $handler->display->display_options['empty']['area']['label'] = 'empty text';
  $handler->display->display_options['empty']['area']['empty'] = TRUE;
  $handler->display->display_options['empty']['area']['content'] = 'No transactions';
  $handler->display->display_options['empty']['area']['format'] = 'editor_filtered_html';
  /* Relationship: Transaction index: 2nd user */
  $handler->display->display_options['relationships']['uid2']['id'] = 'uid2';
  $handler->display->display_options['relationships']['uid2']['table'] = 'mcapi_index';
  $handler->display->display_options['relationships']['uid2']['field'] = 'uid2';
  $handler->display->display_options['relationships']['uid2']['required'] = TRUE;
  /* Field: Transaction index: Created moment */
  $handler->display->display_options['fields']['created']['id'] = 'created';
  $handler->display->display_options['fields']['created']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['created']['field'] = 'created';
  $handler->display->display_options['fields']['created']['label'] = 'Created';
  $handler->display->display_options['fields']['created']['hide_alter_empty'] = FALSE;
  $handler->display->display_options['fields']['created']['date_format'] = 'short';
  /* Field: User: Name */
  $handler->display->display_options['fields']['name']['id'] = 'name';
  $handler->display->display_options['fields']['name']['table'] = 'users';
  $handler->display->display_options['fields']['name']['field'] = 'name';
  $handler->display->display_options['fields']['name']['relationship'] = 'uid2';
  $handler->display->display_options['fields']['name']['label'] = 'With';
  $handler->display->display_options['fields']['name']['hide_alter_empty'] = FALSE;
  /* Field: Transaction index: Expenditure */
  $handler->display->display_options['fields']['expenditure']['id'] = 'expenditure';
  $handler->display->display_options['fields']['expenditure']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['expenditure']['field'] = 'expenditure';
  /* Field: Transaction index: Income */
  $handler->display->display_options['fields']['income']['id'] = 'income';
  $handler->display->display_options['fields']['income']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['income']['field'] = 'income';
  /* Field: Transaction index: Balance */
  $handler->display->display_options['fields']['balance']['id'] = 'balance';
  $handler->display->display_options['fields']['balance']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['balance']['field'] = 'balance';
  $handler->display->display_options['fields']['balance']['hide_alter_empty'] = FALSE;
  /* Sort criterion: Transaction index: Created moment */
  $handler->display->display_options['sorts']['created']['id'] = 'created';
  $handler->display->display_options['sorts']['created']['table'] = 'mcapi_index';
  $handler->display->display_options['sorts']['created']['field'] = 'created';
  $handler->display->display_options['sorts']['created']['order'] = 'DESC';
  /* Contextual filter: Transaction index: 1st user */
  $handler->display->display_options['arguments']['uid1']['id'] = 'uid1';
  $handler->display->display_options['arguments']['uid1']['table'] = 'mcapi_index';
  $handler->display->display_options['arguments']['uid1']['field'] = 'uid1';
  $handler->display->display_options['arguments']['uid1']['default_action'] = 'default';
  $handler->display->display_options['arguments']['uid1']['default_argument_type'] = 'user';
  $handler->display->display_options['arguments']['uid1']['default_argument_options']['user'] = FALSE;
  $handler->display->display_options['arguments']['uid1']['summary']['number_of_records'] = '0';
  $handler->display->display_options['arguments']['uid1']['summary']['format'] = 'default_summary';
  $handler->display->display_options['arguments']['uid1']['summary_options']['items_per_page'] = '25';
  /* Contextual filter: Date: Date (mcapi_index) */
  $handler->display->display_options['arguments']['date_argument']['id'] = 'date_argument';
  $handler->display->display_options['arguments']['date_argument']['table'] = 'mcapi_index';
  $handler->display->display_options['arguments']['date_argument']['field'] = 'date_argument';
  $handler->display->display_options['arguments']['date_argument']['default_action'] = 'default';
  $handler->display->display_options['arguments']['date_argument']['year_range'] = '-10:+0';
  $handler->display->display_options['arguments']['date_argument']['granularity'] = 'year';
  $handler->display->display_options['arguments']['date_argument']['use_fromto'] = 'no';
  $handler->display->display_options['arguments']['date_argument']['date_fields'] = array(
    'mcapi_index.created' => 'mcapi_index.created',
  );
  if ($multicurrencies) {
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
  }

  /* Display: Page */
  $handler = $view->new_display('page', 'Page', 'page');
  $handler->display->display_options['path'] = 'user/%/statement';
  $handler->display->display_options['menu']['type'] = 'tab';
  $handler->display->display_options['menu']['title'] = 'Statement';
  $handler->display->display_options['menu']['weight'] = '0';
  $translatables['statement'] = array(
    t('User transactions'),
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
    t('Statement'),
    t('Created'),
  );
  return $view;
}

function mcapi_getview_user_rankings($multicurrencies) {
  $view = new view;
  $view->name = 'user_rankings';
  $view->description = 'If you have multiple currencies, be sure to change the argument. Also you can add \'between\' dates';
  $view->tag = 'mcapi (index)';
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
  $handler->display->display_options['fields']['expenditure']['element_label_colon'] = FALSE;
  $handler->display->display_options['fields']['expenditure']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['expenditure']['hide_empty'] = 0;
  $handler->display->display_options['fields']['expenditure']['empty_zero'] = 0;
  if ($multicurrencies > 1) {
    /* Contextual filter: Transaction index: Currency */
    $handler->display->display_options['arguments']['currcode']['id'] = 'currcode';
    $handler->display->display_options['arguments']['currcode']['table'] = 'mcapi_index';
    $handler->display->display_options['arguments']['currcode']['field'] = 'currcode';
    $handler->display->display_options['arguments']['currcode']['default_action'] = 'default';
    $handler->display->display_options['arguments']['currcode']['default_argument_type'] = 'php';
    $handler->display->display_options['arguments']['currcode']['default_argument_options']['code'] = '$currencies = &drupal_static("currencies");return array_shift(array_keys($currencies));';
    $handler->display->display_options['arguments']['currcode']['default_argument_skip_url'] = 0;
    $handler->display->display_options['arguments']['currcode']['summary']['number_of_records'] = '0';
    $handler->display->display_options['arguments']['currcode']['summary']['format'] = 'default_summary';
    $handler->display->display_options['arguments']['currcode']['summary_options']['items_per_page'] = '25';
  }
  /* Display: Promiscuity */
  $handler = $view->new_display('block', 'Promiscuity', 'block_promiscuity');
  $handler->display->display_options['defaults']['title'] = FALSE;
  $handler->display->display_options['title'] = 'Promiscuity';
  $handler->display->display_options['defaults']['fields'] = FALSE;
  /* Field: User: Name */
  $handler->display->display_options['fields']['name']['id'] = 'name';
  $handler->display->display_options['fields']['name']['table'] = 'users';
  $handler->display->display_options['fields']['name']['field'] = 'name';
  $handler->display->display_options['fields']['name']['relationship'] = 'uid1';
  $handler->display->display_options['fields']['name']['label'] = '';
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
  $handler = $view->new_display('block', 'Volume', 'block_volumes');
  $handler->display->display_options['defaults']['title'] = FALSE;
  $handler->display->display_options['title'] = 'Volume';
  $handler->display->display_options['defaults']['fields'] = FALSE;
  /* Field: User: Name */
  $handler->display->display_options['fields']['name']['id'] = 'name';
  $handler->display->display_options['fields']['name']['table'] = 'users';
  $handler->display->display_options['fields']['name']['field'] = 'name';
  $handler->display->display_options['fields']['name']['relationship'] = 'uid1';
  $handler->display->display_options['fields']['name']['label'] = '';
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
  $handler = $view->new_display('block', 'Trades', 'block_trades');
  $handler->display->display_options['display_description'] = 'Number of trades';
  $handler->display->display_options['defaults']['fields'] = FALSE;
  /* Field: User: Name */
  $handler->display->display_options['fields']['name']['id'] = 'name';
  $handler->display->display_options['fields']['name']['table'] = 'users';
  $handler->display->display_options['fields']['name']['field'] = 'name';
  $handler->display->display_options['fields']['name']['relationship'] = 'uid1';
  $handler->display->display_options['fields']['name']['label'] = '';
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
  $handler = $view->new_display('block', 'Incomes', 'block_incomes');
  $handler->display->display_options['defaults']['title'] = FALSE;
  $handler->display->display_options['title'] = 'Incomes';
  $handler->display->display_options['defaults']['fields'] = FALSE;
  /* Field: User: Name */
  $handler->display->display_options['fields']['name']['id'] = 'name';
  $handler->display->display_options['fields']['name']['table'] = 'users';
  $handler->display->display_options['fields']['name']['field'] = 'name';
  $handler->display->display_options['fields']['name']['relationship'] = 'uid1';
  $handler->display->display_options['fields']['name']['label'] = '';
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
  $handler = $view->new_display('block', 'Expenditure', 'block_expenditures');
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

function mcapi_getview_balance_history($multicurrencies) {
  $view = new view;
  $view->name = 'running_balance';
  $view->description = t('Running balances per user');
  $view->tag = 'mcapi (index)';
  $view->base_table = 'mcapi_index';
  $view->human_name = t('Running balance');
  $view->core = 7;
  $view->api_version = '3.0';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Master */
  $handler = $view->new_display('default', 'Master', 'default');
  $handler->display->display_options['title'] = 'Running balance';
  $handler->display->display_options['access']['type'] = 'perm';
  $handler->display->display_options['access']['perm'] = 'transact';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['query']['options']['query_comment'] = FALSE;
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'none';
  $handler->display->display_options['style_plugin'] = 'table';
  /* Relationship: Transaction index: 1st user */
  $handler->display->display_options['relationships']['uid1']['id'] = 'uid1';
  $handler->display->display_options['relationships']['uid1']['table'] = 'mcapi_index';
  $handler->display->display_options['relationships']['uid1']['field'] = 'uid1';
  $handler->display->display_options['relationships']['uid1']['required'] = 1;
  /* Field: Transaction index: Balance */
  $handler->display->display_options['fields']['balance']['id'] = 'balance';
  $handler->display->display_options['fields']['balance']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['balance']['field'] = 'balance';
  $handler->display->display_options['fields']['balance']['label'] = 'Balance';
  $handler->display->display_options['fields']['balance']['element_label_colon'] = FALSE;
  $handler->display->display_options['fields']['balance']['element_default_classes'] = 0;
  $handler->display->display_options['fields']['balance']['hide_empty'] = 0;
  $handler->display->display_options['fields']['balance']['empty_zero'] = 0;
  $handler->display->display_options['fields']['balance']['hide_alter_empty'] = 0;
  /* Field: Transaction index: Created moment */
  $handler->display->display_options['fields']['created']['id'] = 'created';
  $handler->display->display_options['fields']['created']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['created']['field'] = 'created';
  $handler->display->display_options['fields']['created']['label'] = 'Created';
  $handler->display->display_options['fields']['created']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['created']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['created']['hide_empty'] = 0;
  $handler->display->display_options['fields']['created']['empty_zero'] = 0;
  $handler->display->display_options['fields']['created']['hide_alter_empty'] = 1;
  $handler->display->display_options['fields']['created']['date_format'] = 'long';
  /* Sort criterion: Transaction index: Created moment */
  $handler->display->display_options['sorts']['created']['id'] = 'created';
  $handler->display->display_options['sorts']['created']['table'] = 'mcapi_index';
  $handler->display->display_options['sorts']['created']['field'] = 'created';
  /* Contextual filter: Transaction index: 1st user */
  $handler->display->display_options['arguments']['uid1']['id'] = 'uid1';
  $handler->display->display_options['arguments']['uid1']['table'] = 'mcapi_index';
  $handler->display->display_options['arguments']['uid1']['field'] = 'uid1';
  $handler->display->display_options['arguments']['uid1']['default_action'] = 'not found';
  $handler->display->display_options['arguments']['uid1']['default_argument_type'] = 'fixed';
  $handler->display->display_options['arguments']['uid1']['default_argument_skip_url'] = 0;
  $handler->display->display_options['arguments']['uid1']['summary']['number_of_records'] = '0';
  $handler->display->display_options['arguments']['uid1']['summary']['format'] = 'default_summary';
  $handler->display->display_options['arguments']['uid1']['summary_options']['items_per_page'] = '25';
  $handler->display->display_options['arguments']['uid1']['break_phrase'] = 0;
  $handler->display->display_options['arguments']['uid1']['not'] = 0;
  if ($multicurrencies) {
    /* Contextual filter: Transaction index: Currency */
    $handler->display->display_options['arguments']['currcode']['id'] = 'currcode';
    $handler->display->display_options['arguments']['currcode']['table'] = 'mcapi_index';
    $handler->display->display_options['arguments']['currcode']['field'] = 'currcode';
    $handler->display->display_options['arguments']['currcode']['default_action'] = 'default';
    $handler->display->display_options['arguments']['currcode']['default_argument_type'] = 'php';
    $handler->display->display_options['arguments']['currcode']['default_argument_options']['code'] = '$currencies = &drupal_static("currencies");return array_shift(array_keys($currencies));';
    $handler->display->display_options['arguments']['currcode']['default_argument_skip_url'] = 0;
    $handler->display->display_options['arguments']['currcode']['summary']['number_of_records'] = '0';
    $handler->display->display_options['arguments']['currcode']['summary']['format'] = 'default_summary';
    $handler->display->display_options['arguments']['currcode']['summary_options']['items_per_page'] = '25';
  }
  /* Display: Block */
  $handler = $view->new_display('block', 'Running balance', 'block_running_balance');
  $handler->display->display_options['block_description'] = 'balance after each transaction';
  $translatables['balance_history'] = array(
    t('Master'),
    t('Balance history'),
    t('Balance'),
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
    t('Running balance'),
  );
  return $view;
}


function mcapi_getview_user_transactions_index($multicurrencies) {
  $view = new view;
  $view->name = 'user_transactions_index';
  $view->description = 'statement of one user, with otheruser field and running balance (reads transaction index table)';
  $view->tag = 'mcapi (index)';
  $view->base_table = 'mcapi_index';
  $view->human_name = 'User transactions';
  $view->core = 7;
  $view->api_version = '3.0';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Master */
  $handler = $view->new_display('default', 'Master', 'default');
  $handler->display->display_options['title'] = 'User transactions';
  $handler->display->display_options['access']['type'] = 'perm';
  $handler->display->display_options['access']['perm'] = 'transact';
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
  $handler->display->display_options['fields']['diff']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['diff']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['diff']['hide_empty'] = 0;
  $handler->display->display_options['fields']['diff']['empty_zero'] = 0;
  $handler->display->display_options['fields']['diff']['hide_alter_empty'] = 0;
  /* Field: Transaction index: Balance */
  $handler->display->display_options['fields']['balance']['id'] = 'balance';
  $handler->display->display_options['fields']['balance']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['balance']['field'] = 'balance';
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
  if ($multicurrencies) {
    $handler->display->display_options['arguments']['currcode']['id'] = 'currcode';
    $handler->display->display_options['arguments']['currcode']['table'] = 'mcapi_index';
    $handler->display->display_options['arguments']['currcode']['field'] = 'currcode';
    $handler->display->display_options['arguments']['currcode']['default_action'] = 'default';
    $handler->display->display_options['arguments']['currcode']['default_argument_type'] = 'fixed';
    $handler->display->display_options['arguments']['currcode']['default_argument_options']['argument'] = 'def_drup';
    $handler->display->display_options['arguments']['currcode']['default_argument_skip_url'] = 0;
    $handler->display->display_options['arguments']['currcode']['summary']['number_of_records'] = '0';
    $handler->display->display_options['arguments']['currcode']['summary']['format'] = 'default_summary';
    $handler->display->display_options['arguments']['currcode']['summary_options']['items_per_page'] = '25';
  }
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

function mcapi_getview_system_stats($multicurrencies) {
  $view = new view;
  $view->name = 'system_stats';
  $view->description = '';
  $view->tag = 'default';
  $view->base_table = 'mcapi_index';
  $view->human_name = 'system stats';
  $view->core = 7;
  $view->api_version = '3.0';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Master */
  $handler = $view->new_display('default', 'Master', 'default');
  $handler->display->display_options['title'] = '';
  $handler->display->display_options['use_more_text'] = 'plus';
  $handler->display->display_options['group_by'] = TRUE;
  $handler->display->display_options['access']['type'] = 'none';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['query']['options']['query_comment'] = FALSE;
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['exposed_form']['options']['reset_button_label'] = 'Remise à zéro';
  $handler->display->display_options['exposed_form']['options']['exposed_sorts_label'] = 'Trier par';
  $handler->display->display_options['pager']['type'] = 'some';
  $handler->display->display_options['pager']['options']['items_per_page'] = '1';
  $handler->display->display_options['style_plugin'] = 'default';
    $handler->display->display_options['row_plugin'] = 'fields';
  /* Field: COUNT(DISTINCT Transaction index: Unique serial number) */
  $handler->display->display_options['fields']['serial']['id'] = 'serial';
  $handler->display->display_options['fields']['serial']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['serial']['field'] = 'serial';
  $handler->display->display_options['fields']['serial']['group_type'] = 'count_distinct';
  $handler->display->display_options['fields']['serial']['label'] = t('Trades');
  /* Champ: COUNT(DISTINCT Transaction index : 2nd user) */
  $handler->display->display_options['fields']['uid2']['id'] = 'uid2';
  $handler->display->display_options['fields']['uid2']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['uid2']['field'] = 'uid2';
  $handler->display->display_options['fields']['uid2']['group_type'] = 'count_distinct';
  $handler->display->display_options['fields']['uid2']['label'] = 'Active traders';
  $handler->display->display_options['fields']['uid2']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['uid2']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['uid2']['hide_empty'] = 0;
  $handler->display->display_options['fields']['uid2']['empty_zero'] = 0;
  $handler->display->display_options['fields']['uid2']['hide_alter_empty'] = 1;
  /* Champ: SUM(Transaction index : Volume) */
  $handler->display->display_options['fields']['volume']['id'] = 'volume';
  $handler->display->display_options['fields']['volume']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['volume']['field'] = 'volume';
  $handler->display->display_options['fields']['volume']['group_type'] = 'sum';
  $handler->display->display_options['fields']['volume']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['volume']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['volume']['hide_empty'] = 0;
  $handler->display->display_options['fields']['volume']['empty_zero'] = 0;
  $handler->display->display_options['fields']['volume']['hide_alter_empty'] = 1;
  if ($multicurrencies) {
    /* Filter criterion: Transaction index: Currency */
    $handler->display->display_options['filters']['currcode']['id'] = 'currcode';
    $handler->display->display_options['filters']['currcode']['table'] = 'mcapi_index';
    $handler->display->display_options['filters']['currcode']['field'] = 'currcode';
    $handler->display->display_options['filters']['currcode']['value'] = array(
      'def_drup' => 'def_drup',
    );
    $handler->display->display_options['filters']['currcode']['exposed'] = TRUE;
    $handler->display->display_options['filters']['currcode']['expose']['operator_id'] = '';
    $handler->display->display_options['filters']['currcode']['expose']['label'] = 'Currency';
    $handler->display->display_options['filters']['currcode']['expose']['operator'] = 'currcode_op';
    $handler->display->display_options['filters']['currcode']['expose']['identifier'] = 'currcode';
    $handler->display->display_options['filters']['currcode']['expose']['reduce'] = 0;
  }
  /* Filtre contextuel: Transaction index : Created moment */
  $handler->display->display_options['arguments']['created']['id'] = 'created';
  $handler->display->display_options['arguments']['created']['table'] = 'mcapi_index';
  $handler->display->display_options['arguments']['created']['field'] = 'created';
  $handler->display->display_options['arguments']['created']['default_argument_type'] = 'fixed';
  $handler->display->display_options['arguments']['created']['default_argument_skip_url'] = 0;
  $handler->display->display_options['arguments']['created']['summary']['number_of_records'] = '0';
  $handler->display->display_options['arguments']['created']['summary']['format'] = 'default_summary';
  $handler->display->display_options['arguments']['created']['summary_options']['items_per_page'] = '25';
  $handler->display->display_options['arguments']['created']['operator'] = '>';
  /* Filter criterion: Transaction index: Value */
  $handler->display->display_options['filters']['diff']['id'] = 'diff';
  $handler->display->display_options['filters']['diff']['table'] = 'mcapi_index';
  $handler->display->display_options['filters']['diff']['field'] = 'diff';
  $handler->display->display_options['filters']['diff']['operator'] = '>';
  $handler->display->display_options['filters']['diff']['value']['value'] = '0';

  /* Display: Block */
  $handler = $view->new_display('block', 'System trading stats', 'block');
  $handler->display->display_options['block_description'] = 'Trading summary';

  /* Display: Bloc */
  $handler = $view->new_display('block', 'User trading stats', 'block_1');
  $handler->display->display_options['title'] = 'User\'s trading summary';
  $handler->display->display_options['defaults']['relationships'] = FALSE;
  /* Relation: Transaction index : 1st user */
  $handler->display->display_options['relationships']['uid1']['id'] = 'uid1';
  $handler->display->display_options['relationships']['uid1']['table'] = 'mcapi_index';
  $handler->display->display_options['relationships']['uid1']['field'] = 'uid1';
  $handler->display->display_options['relationships']['uid1']['required'] = 0;
  $handler->display->display_options['defaults']['fields'] = FALSE;
  /* Field: COUNT(DISTINCT Transaction index: Unique serial number) */
  $handler->display->display_options['fields']['serial']['id'] = 'serial';
  $handler->display->display_options['fields']['serial']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['serial']['field'] = 'serial';
  $handler->display->display_options['fields']['serial']['group_type'] = 'count_distinct';
  $handler->display->display_options['fields']['serial']['label'] = 'Trades';
  /* Field: COUNT(DISTINCT Transaction index: 2nd user) */
  $handler->display->display_options['fields']['uid2']['id'] = 'uid2';
  $handler->display->display_options['fields']['uid2']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['uid2']['field'] = 'uid2';
  $handler->display->display_options['fields']['uid2']['group_type'] = 'count_distinct';
  $handler->display->display_options['fields']['uid2']['label'] = 'Promiscuity';
  /* Field: SUM(Transaction index: Volume) */
  $handler->display->display_options['fields']['volume']['id'] = 'volume';
  $handler->display->display_options['fields']['volume']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['volume']['field'] = 'volume';
  $handler->display->display_options['fields']['volume']['group_type'] = 'sum';
  $handler->display->display_options['defaults']['arguments'] = FALSE;
  /* Filtre contextuel: Transaction index : 1st user */
  $handler->display->display_options['arguments']['uid1']['id'] = 'uid1';
  $handler->display->display_options['arguments']['uid1']['table'] = 'mcapi_index';
  $handler->display->display_options['arguments']['uid1']['field'] = 'uid1';
  $handler->display->display_options['arguments']['uid1']['title_enable'] = 1;
  $handler->display->display_options['arguments']['uid1']['title'] = '%1 summary';
  $handler->display->display_options['arguments']['uid1']['default_argument_type'] = 'fixed';
  $handler->display->display_options['arguments']['uid1']['default_argument_skip_url'] = 0;
  $handler->display->display_options['arguments']['uid1']['summary']['number_of_records'] = '0';
  $handler->display->display_options['arguments']['uid1']['summary']['format'] = 'default_summary';
  $handler->display->display_options['arguments']['uid1']['summary_options']['items_per_page'] = '25';
  $handler->display->display_options['arguments']['uid1']['break_phrase'] = 0;
  $handler->display->display_options['arguments']['uid1']['not'] = 0;
  /* Filtre contextuel: Transaction index : Created moment */
  $handler->display->display_options['arguments']['created']['id'] = 'created';
  $handler->display->display_options['arguments']['created']['table'] = 'mcapi_index';
  $handler->display->display_options['arguments']['created']['field'] = 'created';
  $handler->display->display_options['arguments']['created']['default_argument_type'] = 'fixed';
  $handler->display->display_options['arguments']['created']['default_argument_skip_url'] = 0;
  $handler->display->display_options['arguments']['created']['summary']['number_of_records'] = '0';
  $handler->display->display_options['arguments']['created']['summary']['format'] = 'default_summary';
  $handler->display->display_options['arguments']['created']['summary_options']['items_per_page'] = '25';
  $handler->display->display_options['arguments']['created']['operator'] = '>';

  $handler->display->display_options['defaults']['filter_groups'] = FALSE;
  $handler->display->display_options['defaults']['filters'] = FALSE;
  $translatables['system_stats'] = array(
    t('User\'s trading summary'),
    t('plus'),
    t('Trades'),
    t('Active traders'),
    t('Volume'),
    t('Promiscuity'),
    t('%1 summary'),
  );
  return $view;
}