<?php
// $Id:



function mcapi_views_default_views() {
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
/* Field: Transaction: Value */
$handler->display->display_options['fields']['worth']['id'] = 'worth';
$handler->display->display_options['fields']['worth']['table'] = 'field_data_worth';
$handler->display->display_options['fields']['worth']['field'] = 'worth';
$handler->display->display_options['fields']['worth']['alter']['alter_text'] = 0;
$handler->display->display_options['fields']['worth']['alter']['make_link'] = 0;
$handler->display->display_options['fields']['worth']['alter']['absolute'] = 0;
$handler->display->display_options['fields']['worth']['alter']['external'] = 0;
$handler->display->display_options['fields']['worth']['alter']['replace_spaces'] = 0;
$handler->display->display_options['fields']['worth']['alter']['trim_whitespace'] = 0;
$handler->display->display_options['fields']['worth']['alter']['nl2br'] = 0;
$handler->display->display_options['fields']['worth']['alter']['word_boundary'] = 1;
$handler->display->display_options['fields']['worth']['alter']['ellipsis'] = 1;
$handler->display->display_options['fields']['worth']['alter']['strip_tags'] = 0;
$handler->display->display_options['fields']['worth']['alter']['trim'] = 0;
$handler->display->display_options['fields']['worth']['alter']['html'] = 0;
$handler->display->display_options['fields']['worth']['element_label_colon'] = 1;
$handler->display->display_options['fields']['worth']['element_default_classes'] = 0;
$handler->display->display_options['fields']['worth']['hide_empty'] = 0;
$handler->display->display_options['fields']['worth']['empty_zero'] = 0;
$handler->display->display_options['fields']['worth']['hide_alter_empty'] = 0;
$handler->display->display_options['fields']['worth']['click_sort_column'] = 'currcode';
$handler->display->display_options['fields']['worth']['type'] = 'worth_fields';
$handler->display->display_options['fields']['worth']['field_api_classes'] = 0;
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
$handler->display->display_options['filters']['uid']['expose']['label'] = 'Payee';
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
$handler->display->display_options['filters']['uid_1']['expose']['label'] = 'Payer';
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
$handler->display->display_options['filters']['state']['expose']['required'] = 0;
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

if (module_exists('mcapi_currency')) {
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
}

/* Display: Page */
$handler = $view->new_display('page', 'Page', 'page');
$handler->display->display_options['path'] = 'admin/accounting/transactions';
$handler->display->display_options['menu']['type'] = 'normal';
$handler->display->display_options['menu']['title'] = 'Transactions';
$handler->display->display_options['menu']['description'] = 'Filter through all the transactions on the system';
$handler->display->display_options['menu']['weight'] = '0';
$handler->display->display_options['menu']['name'] = 'management';

  $views[$view->name] = $view;
  return $views;
}