<?php
//Id;

function mcapi_views_default_views() {
  $view = new view;
  $view->name = 'transactions';
  $view->description = '';
  $view->tag = 'default';
  $view->base_table = 'mcapi_transactions';
  $view->human_name = 'transactions';
  $view->core = 7;
  $view->api_version = '3.0';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Master */
  $handler = $view->new_display('default', 'Master', 'default');
  $handler->display->display_options['title'] = t('Transactions');
  $handler->display->display_options['access']['type'] = 'none';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['query']['options']['query_comment'] = FALSE;
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'full';
  $handler->display->display_options['pager']['options']['items_per_page'] = '25';
  $handler->display->display_options['style_plugin'] = 'table';
  /* Relationship: Transaction: Payee */
  $handler->display->display_options['relationships']['payee']['id'] = 'payee';
  $handler->display->display_options['relationships']['payee']['table'] = 'mcapi_transactions';
  $handler->display->display_options['relationships']['payee']['field'] = 'payee';
  $handler->display->display_options['relationships']['payee']['required'] = 0;
  /* Relationship: Transaction: Payer */
  $handler->display->display_options['relationships']['payer']['id'] = 'payer';
  $handler->display->display_options['relationships']['payer']['table'] = 'mcapi_transactions';
  $handler->display->display_options['relationships']['payer']['field'] = 'payer';
  $handler->display->display_options['relationships']['payer']['required'] = 0;
  /* Field: Transaction: Serial number */
  $handler->display->display_options['fields']['serial']['id'] = 'serial';
  $handler->display->display_options['fields']['serial']['table'] = 'mcapi_transactions';
  $handler->display->display_options['fields']['serial']['field'] = 'serial';
  $handler->display->display_options['fields']['serial']['exclude'] = TRUE;
  /* Field: User: Name */
  $handler->display->display_options['fields']['name']['id'] = 'name';
  $handler->display->display_options['fields']['name']['table'] = 'users';
  $handler->display->display_options['fields']['name']['field'] = 'name';
  $handler->display->display_options['fields']['name']['relationship'] = 'payee';
  $handler->display->display_options['fields']['name']['label'] = 'Payee';
  $handler->display->display_options['fields']['name']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['name']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['name']['hide_empty'] = 0;
  $handler->display->display_options['fields']['name']['empty_zero'] = 0;
  $handler->display->display_options['fields']['name']['hide_alter_empty'] = 1;
  $handler->display->display_options['fields']['name']['link_to_user'] = 1;
  $handler->display->display_options['fields']['name']['overwrite_anonymous'] = 0;
  $handler->display->display_options['fields']['name']['format_username'] = 1;
  /* Field: User: Name */
  $handler->display->display_options['fields']['name_1']['id'] = 'name_1';
  $handler->display->display_options['fields']['name_1']['table'] = 'users';
  $handler->display->display_options['fields']['name_1']['field'] = 'name';
  $handler->display->display_options['fields']['name_1']['relationship'] = 'payer';
  $handler->display->display_options['fields']['name_1']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['name_1']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['name_1']['hide_empty'] = 0;
  $handler->display->display_options['fields']['name_1']['empty_zero'] = 0;
  $handler->display->display_options['fields']['name_1']['hide_alter_empty'] = 1;
  $handler->display->display_options['fields']['name_1']['link_to_user'] = 1;
  $handler->display->display_options['fields']['name_1']['overwrite_anonymous'] = 0;
  $handler->display->display_options['fields']['name_1']['format_username'] = 1;
  /* Field: Transaction: Worth */
  $handler->display->display_options['fields']['worth']['id'] = 'worth';
  $handler->display->display_options['fields']['worth']['table'] = 'field_data_worth';
  $handler->display->display_options['fields']['worth']['field'] = 'worth';
  $handler->display->display_options['fields']['worth']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['worth']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['worth']['hide_empty'] = 0;
  $handler->display->display_options['fields']['worth']['empty_zero'] = 0;
  $handler->display->display_options['fields']['worth']['hide_alter_empty'] = 1;
  $handler->display->display_options['fields']['worth']['click_sort_column'] = 'currcode';
  $handler->display->display_options['fields']['worth']['type'] = 'worth_field';
  $handler->display->display_options['fields']['worth']['field_api_classes'] = 0;
  /* Field: Transaction: View */
  $handler->display->display_options['fields']['view']['id'] = 'view';
  $handler->display->display_options['fields']['view']['table'] = 'mcapi_transactions';
  $handler->display->display_options['fields']['view']['field'] = 'view';
  $handler->display->display_options['fields']['view']['label'] = '';
  $handler->display->display_options['fields']['view']['element_label_colon'] = FALSE;
  $handler->display->display_options['fields']['view']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['view']['hide_empty'] = 0;
  $handler->display->display_options['fields']['view']['empty_zero'] = 0;
  $handler->display->display_options['fields']['view']['hide_alter_empty'] = 1;
  /* Sort criterion: Transaction: created */
  $handler->display->display_options['sorts']['created']['id'] = 'created';
  $handler->display->display_options['sorts']['created']['table'] = 'mcapi_transactions';
  $handler->display->display_options['sorts']['created']['field'] = 'created';
  $handler->display->display_options['sorts']['created']['order'] = 'DESC';
  $handler->display->display_options['sorts']['created']['granularity'] = 'hour';
  /* Filter criterion: Transaction: State */
  $handler->display->display_options['filters']['state']['id'] = 'state';
  $handler->display->display_options['filters']['state']['table'] = 'mcapi_transactions';
  $handler->display->display_options['filters']['state']['field'] = 'state';
  $handler->display->display_options['filters']['state']['value'] = array(
    1 => '1',
  );
  /* Filter criterion: Transaction: Type */
  $handler->display->display_options['filters']['type']['id'] = 'type';
  $handler->display->display_options['filters']['type']['table'] = 'mcapi_transactions';
  $handler->display->display_options['filters']['type']['field'] = 'type';
  $handler->display->display_options['filters']['type']['operator'] = 'not in';
  $handler->display->display_options['filters']['type']['value'] = array(
    'child' => 'child',
    'mass' => 'mass',
  );
  $handler->display->display_options['filters']['type']['expose']['operator_id'] = 'type_op';
  $handler->display->display_options['filters']['type']['expose']['label'] = 'Type';
  $handler->display->display_options['filters']['type']['expose']['use_operator'] = FALSE;
  $handler->display->display_options['filters']['type']['expose']['operator'] = 'type_op';
  $handler->display->display_options['filters']['type']['expose']['identifier'] = 'type';
  $handler->display->display_options['filters']['type']['expose']['required'] = FALSE;
  $handler->display->display_options['filters']['type']['expose']['remember'] = FALSE;
  $handler->display->display_options['filters']['type']['expose']['multiple'] = FALSE;

  /* Display: Page */
  $handler = $view->new_display('page', 'Transactions', 'transactions');
  $handler->display->display_options['path'] = 'transactions';
  $handler->display->display_options['menu']['type'] = 'normal';
  $handler->display->display_options['menu']['title'] = 'transactions';
  $handler->display->display_options['menu']['name'] = 'main-menu';

  /* Display: Block */
  $handler = $view->new_display('block', 'Who paid whom', 'who_paid_whom');
  $handler->display->display_options['defaults']['pager'] = FALSE;
  $handler->display->display_options['pager']['type'] = 'some';
  $handler->display->display_options['pager']['options']['items_per_page'] = '5';
  $handler->display->display_options['pager']['options']['offset'] = '0';
  $handler->display->display_options['defaults']['fields'] = FALSE;
  /* Field: User: Name */
  $handler->display->display_options['fields']['name']['id'] = 'name';
  $handler->display->display_options['fields']['name']['table'] = 'users';
  $handler->display->display_options['fields']['name']['field'] = 'name';
  $handler->display->display_options['fields']['name']['relationship'] = 'payee';
  $handler->display->display_options['fields']['name']['label'] = '';
  $handler->display->display_options['fields']['name']['exclude'] = TRUE;
  $handler->display->display_options['fields']['name']['element_label_colon'] = FALSE;
  $handler->display->display_options['fields']['name']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['name']['hide_empty'] = 0;
  $handler->display->display_options['fields']['name']['empty_zero'] = 0;
  $handler->display->display_options['fields']['name']['hide_alter_empty'] = 1;
  $handler->display->display_options['fields']['name']['link_to_user'] = 1;
  $handler->display->display_options['fields']['name']['overwrite_anonymous'] = 0;
  $handler->display->display_options['fields']['name']['format_username'] = 1;
  /* Field: User: Name */
  $handler->display->display_options['fields']['name_1']['id'] = 'name_1';
  $handler->display->display_options['fields']['name_1']['table'] = 'users';
  $handler->display->display_options['fields']['name_1']['field'] = 'name';
  $handler->display->display_options['fields']['name_1']['relationship'] = 'payer';
  $handler->display->display_options['fields']['name_1']['label'] = '';
  $handler->display->display_options['fields']['name_1']['alter']['alter_text'] = 1;
  $handler->display->display_options['fields']['name_1']['alter']['text'] = '[name] paid [name_1]';
  $handler->display->display_options['fields']['name_1']['element_label_colon'] = FALSE;
  $handler->display->display_options['fields']['name_1']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['name_1']['hide_empty'] = 0;
  $handler->display->display_options['fields']['name_1']['empty_zero'] = 0;
  $handler->display->display_options['fields']['name_1']['hide_alter_empty'] = 1;
  $handler->display->display_options['fields']['name_1']['link_to_user'] = 1;
  $handler->display->display_options['fields']['name_1']['overwrite_anonymous'] = 0;
  $handler->display->display_options['fields']['name_1']['format_username'] = 1;

  $views[$view->name] = $view;

  if (module_exists('date_views')) {
    $view = new view();
    $view->name = 'income_expenditure';
    $view->description = 'two displays showing a users transactions in each direction, by period';
    $view->tag = 'mcapi';
    $view->base_table = 'mcapi_transactions';
    $view->human_name = 'Income & Expenditure';
    $view->core = 7;
    $view->api_version = '3.0';
    $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

    /* Display: Master */
    $handler = $view->new_display('default', 'Master', 'default');
    $handler->display->display_options['use_more_always'] = FALSE;
    $handler->display->display_options['access']['type'] = 'none';
    $handler->display->display_options['cache']['type'] = 'none';
    $handler->display->display_options['query']['type'] = 'views_query';
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
    /* Relationship: Transaction: Payee */
    $handler->display->display_options['relationships']['payee']['id'] = 'payee';
    $handler->display->display_options['relationships']['payee']['table'] = 'mcapi_transactions';
    $handler->display->display_options['relationships']['payee']['field'] = 'payee';
    $handler->display->display_options['relationships']['payee']['required'] = TRUE;
    /* Relationship: Transaction: Payer */
    $handler->display->display_options['relationships']['payer']['id'] = 'payer';
    $handler->display->display_options['relationships']['payer']['table'] = 'mcapi_transactions';
    $handler->display->display_options['relationships']['payer']['field'] = 'payer';
    $handler->display->display_options['relationships']['payer']['required'] = TRUE;
    /* Field: Transaction: Serial number */
    $handler->display->display_options['fields']['serial']['id'] = 'serial';
    $handler->display->display_options['fields']['serial']['table'] = 'mcapi_transactions';
    $handler->display->display_options['fields']['serial']['field'] = 'serial';
    /* Sort criterion: Transaction: created */
    $handler->display->display_options['sorts']['created']['id'] = 'created';
    $handler->display->display_options['sorts']['created']['table'] = 'mcapi_transactions';
    $handler->display->display_options['sorts']['created']['field'] = 'created';

    /* Display: Income */
    $handler = $view->new_display('page', 'Income', 'page');
    $handler->display->display_options['defaults']['fields'] = FALSE;
    $handler->display->display_options['defaults']['header'] = FALSE;
    /* Header: Global: Text area */
    $handler->display->display_options['header']['area']['id'] = 'area';
    $handler->display->display_options['header']['area']['table'] = 'views';
    $handler->display->display_options['header']['area']['field'] = 'area';
    $handler->display->display_options['header']['area']['empty'] = TRUE;
    $handler->display->display_options['header']['area']['content'] = '<h4>'.t('Income').'</h4>';
    $handler->display->display_options['header']['area']['format'] = 'editor_filtered_html';

    /* Field: Transaction: Serial number */
    $handler->display->display_options['fields']['serial']['id'] = 'serial';
    $handler->display->display_options['fields']['serial']['table'] = 'mcapi_transactions';
    $handler->display->display_options['fields']['serial']['field'] = 'serial';
    $handler->display->display_options['fields']['serial']['link_to_transaction'] = 0;
    /* Field: Transaction: created */
    $handler->display->display_options['fields']['created']['id'] = 'created';
    $handler->display->display_options['fields']['created']['table'] = 'mcapi_transactions';
    $handler->display->display_options['fields']['created']['field'] = 'created';
    $handler->display->display_options['fields']['created']['label'] = '';
    $handler->display->display_options['fields']['created']['element_label_colon'] = FALSE;
    $handler->display->display_options['fields']['created']['date_format'] = 'short';
    /* Field: User: Name */
    $handler->display->display_options['fields']['name']['id'] = 'name';
    $handler->display->display_options['fields']['name']['table'] = 'users';
    $handler->display->display_options['fields']['name']['field'] = 'name';
    $handler->display->display_options['fields']['name']['relationship'] = 'payer';
    /* Field: Transaction: Worth */
    $handler->display->display_options['fields']['worth']['id'] = 'worth';
    $handler->display->display_options['fields']['worth']['table'] = 'field_data_worth';
    $handler->display->display_options['fields']['worth']['field'] = 'worth';
    $handler->display->display_options['fields']['worth']['label'] = 'Value';
    $handler->display->display_options['fields']['worth']['click_sort_column'] = 'currcode';
    $handler->display->display_options['fields']['worth']['type'] = 'worth_field';
    /* Field: Transaction: Description */
    $handler->display->display_options['fields']['transaction_description']['id'] = 'transaction_description';
    $handler->display->display_options['fields']['transaction_description']['table'] = 'field_data_transaction_description';
    $handler->display->display_options['fields']['transaction_description']['field'] = 'transaction_description';
    $handler->display->display_options['defaults']['arguments'] = FALSE;
    /* Contextual filter: Transaction: Payee */
    $handler->display->display_options['arguments']['payee']['id'] = 'payee';
    $handler->display->display_options['arguments']['payee']['table'] = 'mcapi_transactions';
    $handler->display->display_options['arguments']['payee']['field'] = 'payee';
    $handler->display->display_options['arguments']['payee']['default_action'] = 'default';
    $handler->display->display_options['arguments']['payee']['default_argument_type'] = 'user';
    $handler->display->display_options['arguments']['payee']['default_argument_options']['user'] = FALSE;
    $handler->display->display_options['arguments']['payee']['summary']['number_of_records'] = '0';
    $handler->display->display_options['arguments']['payee']['summary']['format'] = 'default_summary';
    $handler->display->display_options['arguments']['payee']['summary_options']['items_per_page'] = '25';
    /* Contextual filter: Date: Date (mcapi_transactions) */
    $handler->display->display_options['arguments']['date_argument']['id'] = 'date_argument';
    $handler->display->display_options['arguments']['date_argument']['table'] = 'mcapi_transactions';
    $handler->display->display_options['arguments']['date_argument']['field'] = 'date_argument';
    $handler->display->display_options['arguments']['date_argument']['default_action'] = 'default';
    $handler->display->display_options['arguments']['date_argument']['year_range'] = '-10:+0';
    $handler->display->display_options['arguments']['date_argument']['granularity'] = 'month';
    $handler->display->display_options['arguments']['date_argument']['use_fromto'] = 'no';
    $handler->display->display_options['arguments']['date_argument']['date_fields'] = array(
      'mcapi_transactions.created' => 'mcapi_transactions.created',
    );
    $handler->display->display_options['path'] = 'user/%/income_expenditure';
    $handler->display->display_options['menu']['type'] = 'tab';
    $handler->display->display_options['menu']['title'] = 'Trade';
    $handler->display->display_options['menu']['description'] = 'This year\'s transactions';
    $handler->display->display_options['menu']['weight'] = '0';
    $handler->display->display_options['menu']['context'] = 0;


    /* Display: Expenditure */
    $handler = $view->new_display('attachment', 'Expenditure', 'attachment_1');
    $handler->display->display_options['defaults']['header'] = FALSE;
    /* Header: Global: Text area */
    $handler->display->display_options['header']['area']['id'] = 'area';
    $handler->display->display_options['header']['area']['table'] = 'views';
    $handler->display->display_options['header']['area']['field'] = 'area';
    $handler->display->display_options['header']['area']['empty'] = TRUE;
    $handler->display->display_options['header']['area']['content'] = '<h4>'.t('Expenditure').'</h4>';
    $handler->display->display_options['header']['area']['format'] = 'editor_filtered_html';
    $handler->display->display_options['pager']['type'] = 'none';
    $handler->display->display_options['pager']['options']['offset'] = '0';
    $handler->display->display_options['defaults']['fields'] = FALSE;
    /* Field: Transaction: Serial number */
    $handler->display->display_options['fields']['serial']['id'] = 'serial';
    $handler->display->display_options['fields']['serial']['table'] = 'mcapi_transactions';
    $handler->display->display_options['fields']['serial']['field'] = 'serial';
    /* Field: Transaction: created */
    $handler->display->display_options['fields']['created']['id'] = 'created';
    $handler->display->display_options['fields']['created']['table'] = 'mcapi_transactions';
    $handler->display->display_options['fields']['created']['field'] = 'created';
    $handler->display->display_options['fields']['created']['label'] = '';
    $handler->display->display_options['fields']['created']['element_label_colon'] = FALSE;
    $handler->display->display_options['fields']['created']['date_format'] = 'short';
    /* Field: User: Name */
    $handler->display->display_options['fields']['name']['id'] = 'name';
    $handler->display->display_options['fields']['name']['table'] = 'users';
    $handler->display->display_options['fields']['name']['field'] = 'name';
    $handler->display->display_options['fields']['name']['relationship'] = 'payee';
    /* Field: Transaction: Worth */
    $handler->display->display_options['fields']['worth']['id'] = 'worth';
    $handler->display->display_options['fields']['worth']['table'] = 'field_data_worth';
    $handler->display->display_options['fields']['worth']['field'] = 'worth';
    $handler->display->display_options['fields']['worth']['label'] = 'Value';
    $handler->display->display_options['fields']['worth']['alter']['alter_text'] = TRUE;
    $handler->display->display_options['fields']['worth']['alter']['text'] = '-[worth]';
    $handler->display->display_options['fields']['worth']['click_sort_column'] = 'currcode';
    $handler->display->display_options['fields']['worth']['type'] = 'worth_field';
    /* Field: Transaction: Description */
    $handler->display->display_options['fields']['transaction_description']['id'] = 'transaction_description';
    $handler->display->display_options['fields']['transaction_description']['table'] = 'field_data_transaction_description';
    $handler->display->display_options['fields']['transaction_description']['field'] = 'transaction_description';
    $handler->display->display_options['defaults']['arguments'] = FALSE;
    /* Contextual filter: Transaction: Payer */
    $handler->display->display_options['arguments']['payer']['id'] = 'payer';
    $handler->display->display_options['arguments']['payer']['table'] = 'mcapi_transactions';
    $handler->display->display_options['arguments']['payer']['field'] = 'payer';
    $handler->display->display_options['arguments']['payer']['default_action'] = 'default';
    $handler->display->display_options['arguments']['payer']['default_argument_type'] = 'user';
    $handler->display->display_options['arguments']['payer']['default_argument_options']['user'] = FALSE;
    $handler->display->display_options['arguments']['payer']['summary']['number_of_records'] = '0';
    $handler->display->display_options['arguments']['payer']['summary']['format'] = 'default_summary';
    $handler->display->display_options['arguments']['payer']['summary_options']['items_per_page'] = '25';
    /* Contextual filter: Date: Date (mcapi_transactions) */
    $handler->display->display_options['arguments']['date_argument']['id'] = 'date_argument';
    $handler->display->display_options['arguments']['date_argument']['table'] = 'mcapi_transactions';
    $handler->display->display_options['arguments']['date_argument']['field'] = 'date_argument';
    $handler->display->display_options['arguments']['date_argument']['default_action'] = 'default';
    $handler->display->display_options['arguments']['date_argument']['year_range'] = '-10:+0';
    $handler->display->display_options['arguments']['date_argument']['granularity'] = 'year';
    $handler->display->display_options['arguments']['date_argument']['use_fromto'] = 'no';
    $handler->display->display_options['arguments']['date_argument']['date_fields'] = array(
      'mcapi_transactions.created' => 'mcapi_transactions.created',
    );
    $handler->display->display_options['displays'] = array(
      'page' => 'page',
      'default' => 0,
    );
    $handler->display->display_options['attachment_position'] = 'after';
    $views[$view->name] = $view;
  }
  else {
    if ($GLOBALS['user']->uid == 1) drupal_set_message('Install date_views module for more default views.', 'warning');
  }
  return $views;
}

$translatables['transactions'] = array(
  t('more'),
  t('Payee'),
  t('Payer'),
  t('Serial number'),
  t('Name'),
  t('Worth'),
  t('Value'),
  t('Who paid whom'),
  t('[name] paid [name_1]'),
  t('Income & Expenditure'),
  t('Transactions'),
  t('No transactions'),
  t('Trade'),
  t('This year\'s transactions'),
  t('Transactions'),
  t('Transactions'),
  ''
);