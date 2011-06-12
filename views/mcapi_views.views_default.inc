<?php
//Id;

function mcapi_views_views_default_views() {
  
  $view = new view;
  $view->name = 'user_trades';
  $view->description = '';
  $view->tag = 'mcapi';
  $view->base_table = 'mcapi_index';
  $view->human_name = 'user_trades';
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
  /* Field: COUNT(DISTINCT Transaction index: 2nd Person) */
  $handler->display->display_options['fields']['uid2']['id'] = 'uid2';
  $handler->display->display_options['fields']['uid2']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['uid2']['field'] = 'uid2';
  $handler->display->display_options['fields']['uid2']['group_type'] = 'count_distinct';
  $handler->display->display_options['fields']['uid2']['label'] = 'Promiscuity';
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
  /* Field: Transaction index: Income */
  $handler->display->display_options['fields']['income']['id'] = 'income';
  $handler->display->display_options['fields']['income']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['income']['field'] = 'income';
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
  /* Field: Global: PHP */
  $handler->display->display_options['fields']['php']['id'] = 'php';
  $handler->display->display_options['fields']['php']['table'] = 'views';
  $handler->display->display_options['fields']['php']['field'] = 'php';
  $handler->display->display_options['fields']['php']['label'] = 'Balance';
  $handler->display->display_options['fields']['php']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['php']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['php']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['php']['alter']['external'] = 0;
  $handler->display->display_options['fields']['php']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['php']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['php']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['php']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['php']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['php']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['php']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['php']['alter']['html'] = 0;
  $handler->display->display_options['fields']['php']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['php']['element_default_classes'] = 0;
  $handler->display->display_options['fields']['php']['hide_empty'] = 0;
  $handler->display->display_options['fields']['php']['empty_zero'] = 0;
  $handler->display->display_options['fields']['php']['use_php_setup'] = 0;
  $handler->display->display_options['fields']['php']['php_value'] = 'return $row->income - $row->expenditure;';
  $handler->display->display_options['fields']['php']['php_output'] = '<?php print mcapi_format_money($value, $data->cid); ?>';
  $handler->display->display_options['fields']['php']['use_php_click_sortable'] = '1';
  $handler->display->display_options['fields']['php']['php_click_sortable'] = '';
  /* Field: COUNT(Transaction index: 2nd Person) */
  $handler->display->display_options['fields']['uid2_1']['id'] = 'uid2_1';
  $handler->display->display_options['fields']['uid2_1']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['uid2_1']['field'] = 'uid2';
  $handler->display->display_options['fields']['uid2_1']['group_type'] = 'count';
  $handler->display->display_options['fields']['uid2_1']['label'] = 'Trades';
  $handler->display->display_options['fields']['uid2_1']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['uid2_1']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['uid2_1']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['uid2_1']['alter']['external'] = 0;
  $handler->display->display_options['fields']['uid2_1']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['uid2_1']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['uid2_1']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['uid2_1']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['uid2_1']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['uid2_1']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['uid2_1']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['uid2_1']['alter']['html'] = 0;
  $handler->display->display_options['fields']['uid2_1']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['uid2_1']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['uid2_1']['hide_empty'] = 0;
  $handler->display->display_options['fields']['uid2_1']['empty_zero'] = 0;
  $handler->display->display_options['fields']['uid2_1']['format_plural'] = 0;
  /* Contextual filter: Transaction index: 1st user */
  $handler->display->display_options['arguments']['uid1']['id'] = 'uid1';
  $handler->display->display_options['arguments']['uid1']['table'] = 'mcapi_index';
  $handler->display->display_options['arguments']['uid1']['field'] = 'uid1';
  $handler->display->display_options['arguments']['uid1']['default_argument_type'] = 'fixed';
  $handler->display->display_options['arguments']['uid1']['default_argument_skip_url'] = 0;
  $handler->display->display_options['arguments']['uid1']['summary']['number_of_records'] = '0';
  $handler->display->display_options['arguments']['uid1']['summary']['format'] = 'default_summary';
  $handler->display->display_options['arguments']['uid1']['summary_options']['items_per_page'] = '25';
  /* Filter criterion: User: Created date */
  $handler->display->display_options['filters']['created_1']['id'] = 'created_1';
  $handler->display->display_options['filters']['created_1']['table'] = 'users';
  $handler->display->display_options['filters']['created_1']['field'] = 'created';
  $handler->display->display_options['filters']['created_1']['operator'] = 'between';
  $handler->display->display_options['filters']['created_1']['value']['type'] = 'offset';
  $handler->display->display_options['filters']['created_1']['exposed'] = TRUE;
  $handler->display->display_options['filters']['created_1']['expose']['operator_id'] = 'created_1_op';
  $handler->display->display_options['filters']['created_1']['expose']['label'] = 'Created between';
  $handler->display->display_options['filters']['created_1']['expose']['operator'] = 'created_1_op';
  $handler->display->display_options['filters']['created_1']['expose']['identifier'] = 'created_1';
  $handler->display->display_options['filters']['created_1']['expose']['multiple'] = FALSE;
  /* Filter criterion: Transaction index: Currency */
  $handler->display->display_options['filters']['cid']['id'] = 'cid';
  $handler->display->display_options['filters']['cid']['table'] = 'mcapi_index';
  $handler->display->display_options['filters']['cid']['field'] = 'cid';
  $handler->display->display_options['filters']['cid']['exposed'] = TRUE;
  $handler->display->display_options['filters']['cid']['expose']['operator_id'] = 'cid_op';
  $handler->display->display_options['filters']['cid']['expose']['label'] = 'Currency';
  $handler->display->display_options['filters']['cid']['expose']['operator'] = 'cid_op';
  $handler->display->display_options['filters']['cid']['expose']['identifier'] = 'cid';
  $handler->display->display_options['filters']['cid']['expose']['required'] = 1;
  $handler->display->display_options['filters']['cid']['expose']['multiple'] = 1;
  $handler->display->display_options['filters']['cid']['expose']['reduce'] = 0;
  $translatables['user_trades'] = array(
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
    t('Balance'),
    t('Trades'),
    t('All'),
    t('Created between'),
  );

  $views[$view->name] = $view;

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
  /* Field: COUNT(Transaction: Amount) */
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
  /* Field: SUM(Transaction: Amount) */
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
  $handler->display->display_options['filters']['cid']['id'] = 'cid';
  $handler->display->display_options['filters']['cid']['table'] = 'mcapi_transactions';
  $handler->display->display_options['filters']['cid']['field'] = 'cid';
  $handler->display->display_options['filters']['cid']['exposed'] = TRUE;
  $handler->display->display_options['filters']['cid']['expose']['operator_id'] = 'cid_op';
  $handler->display->display_options['filters']['cid']['expose']['label'] = 'Currency ID';
  $handler->display->display_options['filters']['cid']['expose']['operator'] = 'cid_op';
  $handler->display->display_options['filters']['cid']['expose']['identifier'] = 'cid';
  $handler->display->display_options['filters']['cid']['expose']['reduce'] = 0;
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

  /* Display: Page */
  $handler = $view->new_display('page', 'Page', 'page_1');
  $handler->display->display_options['path'] = 'transactions/stats';
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

  $view = new view;
  $view->name = 'user_rankings';
  $view->description = 'If you have multiple currencies, be sure to change the argument. Also you can add \'between\' dates';
  $view->tag = 'default';
  $view->base_table = 'users';
  $view->human_name = 'user_ranking';
  $view->core = 7;
  $view->api_version = '3.0-alpha1';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Master */
  $handler = $view->new_display('default', 'Master', 'default');
  $handler->display->display_options['title'] = 'rankings';
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
  /* Field: User: Name */
  $handler->display->display_options['fields']['name']['id'] = 'name';
  $handler->display->display_options['fields']['name']['table'] = 'users';
  $handler->display->display_options['fields']['name']['field'] = 'name';
  $handler->display->display_options['fields']['name']['label'] = '';
  $handler->display->display_options['fields']['name']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['name']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['name']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['name']['alter']['word_boundary'] = 0;
  $handler->display->display_options['fields']['name']['alter']['ellipsis'] = 0;
  $handler->display->display_options['fields']['name']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['name']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['name']['alter']['html'] = 0;
  $handler->display->display_options['fields']['name']['hide_empty'] = 0;
  $handler->display->display_options['fields']['name']['empty_zero'] = 0;
  $handler->display->display_options['fields']['name']['link_to_user'] = 1;
  $handler->display->display_options['fields']['name']['overwrite_anonymous'] = 0;
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
  /* Sort criterion: SUM(Transaction index: Expenditure) */
  $handler->display->display_options['sorts']['expenditure']['id'] = 'expenditure';
  $handler->display->display_options['sorts']['expenditure']['table'] = 'mcapi_index';
  $handler->display->display_options['sorts']['expenditure']['field'] = 'expenditure';
  $handler->display->display_options['sorts']['expenditure']['group_type'] = 'sum';
  $handler->display->display_options['sorts']['expenditure']['order'] = 'DESC';
  /* Contextual filter: Transaction index: Currency */
  $handler->display->display_options['arguments']['cid']['id'] = 'cid';
  $handler->display->display_options['arguments']['cid']['table'] = 'mcapi_index';
  $handler->display->display_options['arguments']['cid']['field'] = 'cid';
  $handler->display->display_options['arguments']['cid']['default_action'] = 'default';
  $handler->display->display_options['arguments']['cid']['default_argument_type'] = 'php';
  $handler->display->display_options['arguments']['cid']['default_argument_options']['code'] = '$currencies = currencies_load();
  return array_shift(array_keys($currencies));';
  $handler->display->display_options['arguments']['cid']['default_argument_skip_url'] = 0;
  $handler->display->display_options['arguments']['cid']['summary']['number_of_records'] = '0';
  $handler->display->display_options['arguments']['cid']['summary']['format'] = 'default_summary';
  $handler->display->display_options['arguments']['cid']['summary_options']['items_per_page'] = '25';
  /* Filter criterion: User: Name */
  $handler->display->display_options['filters']['uid']['id'] = 'uid';
  $handler->display->display_options['filters']['uid']['table'] = 'users';
  $handler->display->display_options['filters']['uid']['field'] = 'uid';
  $handler->display->display_options['filters']['uid']['operator'] = 'not in';
  $handler->display->display_options['filters']['uid']['value'] = array(
    0 => 0,
  );
  $handler->display->display_options['filters']['uid']['expose']['operator_id'] = 'uid_op';
  $handler->display->display_options['filters']['uid']['expose']['label'] = 'Name';
  $handler->display->display_options['filters']['uid']['expose']['operator'] = 'uid_op';
  $handler->display->display_options['filters']['uid']['expose']['identifier'] = 'uid';
  $handler->display->display_options['filters']['uid']['expose']['multiple'] = FALSE;
  $handler->display->display_options['filters']['uid']['expose']['reduce'] = 0;

  /* Display: Promiscuity */
  $handler = $view->new_display('block', 'Promiscuity', 'block_1');
  $handler->display->display_options['defaults']['title'] = FALSE;
  $handler->display->display_options['title'] = 'Promiscuity';
  $handler->display->display_options['defaults']['fields'] = FALSE;
  /* Field: User: Name */
  $handler->display->display_options['fields']['name']['id'] = 'name';
  $handler->display->display_options['fields']['name']['table'] = 'users';
  $handler->display->display_options['fields']['name']['field'] = 'name';
  $handler->display->display_options['fields']['name']['label'] = '';
  $handler->display->display_options['fields']['name']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['name']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['name']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['name']['alter']['word_boundary'] = 0;
  $handler->display->display_options['fields']['name']['alter']['ellipsis'] = 0;
  $handler->display->display_options['fields']['name']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['name']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['name']['alter']['html'] = 0;
  $handler->display->display_options['fields']['name']['hide_empty'] = 0;
  $handler->display->display_options['fields']['name']['empty_zero'] = 0;
  $handler->display->display_options['fields']['name']['link_to_user'] = 1;
  $handler->display->display_options['fields']['name']['overwrite_anonymous'] = 0;
  /* Field: COUNT(DISTINCT Transaction index: 2nd Person) */
  $handler->display->display_options['fields']['uid2']['id'] = 'uid2';
  $handler->display->display_options['fields']['uid2']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['uid2']['field'] = 'uid2';
  $handler->display->display_options['fields']['uid2']['group_type'] = 'count_distinct';
  $handler->display->display_options['fields']['uid2']['label'] = '';
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
  $handler->display->display_options['fields']['uid2']['element_label_colon'] = FALSE;
  $handler->display->display_options['fields']['uid2']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['uid2']['hide_empty'] = 0;
  $handler->display->display_options['fields']['uid2']['empty_zero'] = 0;
  $handler->display->display_options['fields']['uid2']['format_plural'] = 0;
  $handler->display->display_options['defaults']['sorts'] = FALSE;
  /* Sort criterion: COUNT(DISTINCT Transaction index: 2nd Person) */
  $handler->display->display_options['sorts']['uid2']['id'] = 'uid2';
  $handler->display->display_options['sorts']['uid2']['table'] = 'mcapi_index';
  $handler->display->display_options['sorts']['uid2']['field'] = 'uid2';
  $handler->display->display_options['sorts']['uid2']['group_type'] = 'count_distinct';
  $handler->display->display_options['sorts']['uid2']['order'] = 'DESC';

  /* Display: Volume */
  $handler = $view->new_display('block', 'Volume', 'block_2');
  $handler->display->display_options['defaults']['title'] = FALSE;
  $handler->display->display_options['title'] = 'Volume';
  $handler->display->display_options['defaults']['fields'] = FALSE;
  /* Field: User: Name */
  $handler->display->display_options['fields']['name']['id'] = 'name';
  $handler->display->display_options['fields']['name']['table'] = 'users';
  $handler->display->display_options['fields']['name']['field'] = 'name';
  $handler->display->display_options['fields']['name']['label'] = '';
  $handler->display->display_options['fields']['name']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['name']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['name']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['name']['alter']['word_boundary'] = 0;
  $handler->display->display_options['fields']['name']['alter']['ellipsis'] = 0;
  $handler->display->display_options['fields']['name']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['name']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['name']['alter']['html'] = 0;
  $handler->display->display_options['fields']['name']['hide_empty'] = 0;
  $handler->display->display_options['fields']['name']['empty_zero'] = 0;
  $handler->display->display_options['fields']['name']['link_to_user'] = 1;
  $handler->display->display_options['fields']['name']['overwrite_anonymous'] = 0;
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
  $handler->display->display_options['fields']['name']['label'] = '';
  $handler->display->display_options['fields']['name']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['name']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['name']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['name']['alter']['word_boundary'] = 0;
  $handler->display->display_options['fields']['name']['alter']['ellipsis'] = 0;
  $handler->display->display_options['fields']['name']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['name']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['name']['alter']['html'] = 0;
  $handler->display->display_options['fields']['name']['hide_empty'] = 0;
  $handler->display->display_options['fields']['name']['empty_zero'] = 0;
  $handler->display->display_options['fields']['name']['link_to_user'] = 1;
  $handler->display->display_options['fields']['name']['overwrite_anonymous'] = 0;
  /* Field: COUNT(Transaction index: 2nd Person) */
  $handler->display->display_options['fields']['uid2']['id'] = 'uid2';
  $handler->display->display_options['fields']['uid2']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['uid2']['field'] = 'uid2';
  $handler->display->display_options['fields']['uid2']['group_type'] = 'count';
  $handler->display->display_options['fields']['uid2']['label'] = 'Trades';
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
  $handler->display->display_options['fields']['uid2']['format_plural'] = 0;
  $handler->display->display_options['defaults']['sorts'] = FALSE;
  /* Sort criterion: COUNT(Transaction index: 2nd Person) */
  $handler->display->display_options['sorts']['uid2']['id'] = 'uid2';
  $handler->display->display_options['sorts']['uid2']['table'] = 'mcapi_index';
  $handler->display->display_options['sorts']['uid2']['field'] = 'uid2';
  $handler->display->display_options['sorts']['uid2']['group_type'] = 'count';
  $handler->display->display_options['sorts']['uid2']['order'] = 'DESC';

  /* Display: Incomes */
  $handler = $view->new_display('block', 'Incomes', 'block_4');
  $handler->display->display_options['defaults']['title'] = FALSE;
  $handler->display->display_options['title'] = 'incomes';
  $handler->display->display_options['defaults']['fields'] = FALSE;
  /* Field: User: Name */
  $handler->display->display_options['fields']['name']['id'] = 'name';
  $handler->display->display_options['fields']['name']['table'] = 'users';
  $handler->display->display_options['fields']['name']['field'] = 'name';
  $handler->display->display_options['fields']['name']['label'] = '';
  $handler->display->display_options['fields']['name']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['name']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['name']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['name']['alter']['word_boundary'] = 0;
  $handler->display->display_options['fields']['name']['alter']['ellipsis'] = 0;
  $handler->display->display_options['fields']['name']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['name']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['name']['alter']['html'] = 0;
  $handler->display->display_options['fields']['name']['hide_empty'] = 0;
  $handler->display->display_options['fields']['name']['empty_zero'] = 0;
  $handler->display->display_options['fields']['name']['link_to_user'] = 1;
  $handler->display->display_options['fields']['name']['overwrite_anonymous'] = 0;
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
  $translatables['user_rankings'] = array(
    t('Master'),
    t('rankings'),
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
  $views[$view->name] = $view;


  $view = new view;
  $view->name = 'balance_history';
  $view->description = 'unixtimes and running balances';
  $view->tag = 'mcapi';
  $view->base_table = 'mcapi_index';
  $view->human_name = 'balance_history';
  $view->core = 7;
  $view->api_version = '3.0-alpha1';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Master */
  $handler = $view->new_display('default', 'Master', 'default');
  $handler->display->display_options['title'] = 'balance_history';
  $handler->display->display_options['access']['type'] = 'none';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['query']['options']['query_comment'] = FALSE;
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'full';
  $handler->display->display_options['pager']['options']['items_per_page'] = '';
  $handler->display->display_options['style_plugin'] = 'table';
  $handler->display->display_options['style_options']['grouping'] = 'cid';
  $handler->display->display_options['style_options']['columns'] = array(
    'balance' => 'balance',
    'created' => 'created',
    'cid' => 'cid',
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
    'cid' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
    ),
  );
  $handler->display->display_options['style_options']['override'] = 1;
  $handler->display->display_options['style_options']['sticky'] = 0;
  $handler->display->display_options['style_options']['empty_table'] = 0;
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
  /* Field: Transaction index: Created moment */
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
  /* Field: Transaction index: Currency */
  $handler->display->display_options['fields']['cid']['id'] = 'cid';
  $handler->display->display_options['fields']['cid']['table'] = 'mcapi_index';
  $handler->display->display_options['fields']['cid']['field'] = 'cid';
  $handler->display->display_options['fields']['cid']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['cid']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['cid']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['cid']['alter']['external'] = 0;
  $handler->display->display_options['fields']['cid']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['cid']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['cid']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['cid']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['cid']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['cid']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['cid']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['cid']['alter']['html'] = 0;
  $handler->display->display_options['fields']['cid']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['cid']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['cid']['hide_empty'] = 0;
  $handler->display->display_options['fields']['cid']['empty_zero'] = 0;
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
  $handler = $view->new_display('block', 'Block', 'block');
  $translatables['balance_history'] = array(
    t('Master'),
    t('balance_history'),
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
    t('Created moment'),
    t('Currency'),
    t('All'),
    t('Block'),
  );
  $views[$view->name] = $view;



  //$views[$view->name] = $view;
  return $views;
}
