<?php

/*
 * Implements views hook_views_default_views
 */
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
  $handler->display->display_options['access']['perm'] = 'manage all transactions';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'full';
  $handler->display->display_options['pager']['options']['items_per_page'] = '50';
  $handler->display->display_options['style_plugin'] = 'table';
  $handler->display->display_options['style_options']['columns'] = array(
    'created' => 'created',
    'description' => 'description',
    'payee' => 'payee',
    'payer' => 'payer',
    'type' => 'type',
    'state' => 'state',
    'worth' => 'worth',
    'field_cat' => 'field_cat',
  );
  $handler->display->display_options['style_options']['default'] = 'created';
  $handler->display->display_options['style_options']['info'] = array(
    'description' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
    ),
    'description_1' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
    ),
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
  /* Field: transactions: Timestamp */
  $handler->display->display_options['fields']['created']['id'] = 'created';
  $handler->display->display_options['fields']['created']['table'] = 'mcapi_transactions';
  $handler->display->display_options['fields']['created']['field'] = 'created';
  $handler->display->display_options['fields']['created']['label'] = 'Created';
  $handler->display->display_options['fields']['created']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['created']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['created']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['created']['alter']['external'] = 0;
  $handler->display->display_options['fields']['created']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['created']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['created']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['created']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['created']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['created']['alter']['html'] = 0;
  $handler->display->display_options['fields']['created']['element_type_enable'] = 0;
  $handler->display->display_options['fields']['created']['element_class_enable'] = 0;
  $handler->display->display_options['fields']['created']['element_label_type_enable'] = 0;
  $handler->display->display_options['fields']['created']['element_label_class_enable'] = 0;
  $handler->display->display_options['fields']['created']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['created']['element_wrapper_type_enable'] = 0;
  $handler->display->display_options['fields']['created']['element_wrapper_class_enable'] = 0;
  $handler->display->display_options['fields']['created']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['created']['hide_empty'] = 0;
  $handler->display->display_options['fields']['created']['empty_zero'] = 0;
  /* Field: transactions: Description */
  $handler->display->display_options['fields']['description']['id'] = 'description';
  $handler->display->display_options['fields']['description']['table'] = 'mcapi_transactions';
  $handler->display->display_options['fields']['description']['field'] = 'description';
  /* Field: transactions: Payee */
  $handler->display->display_options['fields']['payee']['id'] = 'payee';
  $handler->display->display_options['fields']['payee']['table'] = 'mcapi_transactions';
  $handler->display->display_options['fields']['payee']['field'] = 'payee';
  $handler->display->display_options['fields']['payee']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['payee']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['payee']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['payee']['alter']['external'] = 0;
  $handler->display->display_options['fields']['payee']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['payee']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['payee']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['payee']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['payee']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['payee']['alter']['html'] = 0;
  $handler->display->display_options['fields']['payee']['element_type_enable'] = 0;
  $handler->display->display_options['fields']['payee']['element_class_enable'] = 0;
  $handler->display->display_options['fields']['payee']['element_label_type_enable'] = 0;
  $handler->display->display_options['fields']['payee']['element_label_class_enable'] = 0;
  $handler->display->display_options['fields']['payee']['element_label_colon'] = 0;
  $handler->display->display_options['fields']['payee']['element_wrapper_type_enable'] = 0;
  $handler->display->display_options['fields']['payee']['element_wrapper_class_enable'] = 0;
  $handler->display->display_options['fields']['payee']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['payee']['hide_empty'] = 0;
  $handler->display->display_options['fields']['payee']['empty_zero'] = 0;
  /* Field: transactions: Payer */
  $handler->display->display_options['fields']['payer']['id'] = 'payer';
  $handler->display->display_options['fields']['payer']['table'] = 'mcapi_transactions';
  $handler->display->display_options['fields']['payer']['field'] = 'payer';
  $handler->display->display_options['fields']['payer']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['payer']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['payer']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['payer']['alter']['external'] = 0;
  $handler->display->display_options['fields']['payer']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['payer']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['payer']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['payer']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['payer']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['payer']['alter']['html'] = 0;
  $handler->display->display_options['fields']['payer']['element_type_enable'] = 0;
  $handler->display->display_options['fields']['payer']['element_class_enable'] = 0;
  $handler->display->display_options['fields']['payer']['element_label_type_enable'] = 0;
  $handler->display->display_options['fields']['payer']['element_label_class_enable'] = 0;
  $handler->display->display_options['fields']['payer']['element_label_colon'] = 0;
  $handler->display->display_options['fields']['payer']['element_wrapper_type_enable'] = 0;
  $handler->display->display_options['fields']['payer']['element_wrapper_class_enable'] = 0;
  $handler->display->display_options['fields']['payer']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['payer']['hide_empty'] = 0;
  $handler->display->display_options['fields']['payer']['empty_zero'] = 0;
  /* Field: transactions: Type */
  $handler->display->display_options['fields']['type']['id'] = 'type';
  $handler->display->display_options['fields']['type']['table'] = 'mcapi_transactions';
  $handler->display->display_options['fields']['type']['field'] = 'type';
  $handler->display->display_options['fields']['type']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['type']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['type']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['type']['alter']['external'] = 0;
  $handler->display->display_options['fields']['type']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['type']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['type']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['type']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['type']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['type']['alter']['html'] = 0;
  $handler->display->display_options['fields']['type']['element_type_enable'] = 0;
  $handler->display->display_options['fields']['type']['element_class_enable'] = 0;
  $handler->display->display_options['fields']['type']['element_label_type_enable'] = 0;
  $handler->display->display_options['fields']['type']['element_label_class_enable'] = 0;
  $handler->display->display_options['fields']['type']['element_label_colon'] = 0;
  $handler->display->display_options['fields']['type']['element_wrapper_type_enable'] = 0;
  $handler->display->display_options['fields']['type']['element_wrapper_class_enable'] = 0;
  $handler->display->display_options['fields']['type']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['type']['hide_empty'] = 0;
  $handler->display->display_options['fields']['type']['empty_zero'] = 0;
  /* Field: transactions: transaction state */
  $handler->display->display_options['fields']['state']['id'] = 'state';
  $handler->display->display_options['fields']['state']['table'] = 'mcapi_transactions';
  $handler->display->display_options['fields']['state']['field'] = 'state';
  $handler->display->display_options['fields']['state']['label'] = 'State';
  $handler->display->display_options['fields']['state']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['state']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['state']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['state']['alter']['external'] = 0;
  $handler->display->display_options['fields']['state']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['state']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['state']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['state']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['state']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['state']['alter']['html'] = 0;
  $handler->display->display_options['fields']['state']['element_type_enable'] = 0;
  $handler->display->display_options['fields']['state']['element_class_enable'] = 0;
  $handler->display->display_options['fields']['state']['element_label_type_enable'] = 0;
  $handler->display->display_options['fields']['state']['element_label_class_enable'] = 0;
  $handler->display->display_options['fields']['state']['element_label_colon'] = 0;
  $handler->display->display_options['fields']['state']['element_wrapper_type_enable'] = 0;
  $handler->display->display_options['fields']['state']['element_wrapper_class_enable'] = 0;
  $handler->display->display_options['fields']['state']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['state']['hide_empty'] = 0;
  $handler->display->display_options['fields']['state']['empty_zero'] = 0;
  $handler->display->display_options['fields']['state']['not'] = 0;
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
  $handler->display->display_options['fields']['worth']['element_label_colon'] = 0;
  $handler->display->display_options['fields']['worth']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['worth']['hide_empty'] = 0;
  $handler->display->display_options['fields']['worth']['empty_zero'] = 0;
  $handler->display->display_options['fields']['worth']['hide_alter_empty'] = 0;
  $handler->display->display_options['fields']['worth']['click_sort_column'] = 'currcode';
  $handler->display->display_options['fields']['worth']['field_api_classes'] = 0;
  /* Sort criterion: transactions: Timestamp */
  $handler->display->display_options['sorts']['created']['id'] = 'created';
  $handler->display->display_options['sorts']['created']['table'] = 'mcapi_transactions';
  $handler->display->display_options['sorts']['created']['field'] = 'created';
  $handler->display->display_options['sorts']['created']['order'] = 'DESC';
  $handler->display->display_options['sorts']['created']['granularity'] = 'minute';
  /* Filter criterion: transactions: value */
  $handler->display->display_options['filters']['quantity']['id'] = 'quantity';
  $handler->display->display_options['filters']['quantity']['table'] = 'mcapi_transactions';
  $handler->display->display_options['filters']['quantity']['field'] = 'quantity';
  $handler->display->display_options['filters']['quantity']['operator'] = '>';
  $handler->display->display_options['filters']['quantity']['exposed'] = TRUE;
  $handler->display->display_options['filters']['quantity']['expose']['operator_id'] = 'quantity_op';
  $handler->display->display_options['filters']['quantity']['expose']['label'] = 'value';
  $handler->display->display_options['filters']['quantity']['expose']['use_operator'] = 1;
  $handler->display->display_options['filters']['quantity']['expose']['operator'] = 'quantity_op';
  $handler->display->display_options['filters']['quantity']['expose']['identifier'] = 'quantity';
  $handler->display->display_options['filters']['quantity']['expose']['multiple'] = FALSE;
  /* Filter criterion: transactions: Currency ID */
  $handler->display->display_options['filters']['currcode']['id'] = 'currcode';
  $handler->display->display_options['filters']['currcode']['table'] = 'mcapi_transactions';
  $handler->display->display_options['filters']['currcode']['field'] = 'currcode';
  $handler->display->display_options['filters']['currcode']['exposed'] = TRUE;
  $handler->display->display_options['filters']['currcode']['expose']['operator_id'] = 'currcode_op';
  $handler->display->display_options['filters']['currcode']['expose']['label'] = 'Currency ID';
  $handler->display->display_options['filters']['currcode']['expose']['use_operator'] = TRUE;
  $handler->display->display_options['filters']['currcode']['expose']['operator'] = 'currcode_op';
  $handler->display->display_options['filters']['currcode']['expose']['identifier'] = 'currcode';
  $handler->display->display_options['filters']['currcode']['expose']['reduce'] = 0;
  /* Filter criterion: transactions: Description */
  $handler->display->display_options['filters']['description']['id'] = 'description';
  $handler->display->display_options['filters']['description']['table'] = 'mcapi_transactions';
  $handler->display->display_options['filters']['description']['field'] = 'description';
  $handler->display->display_options['filters']['description']['exposed'] = TRUE;
  $handler->display->display_options['filters']['description']['expose']['operator_id'] = 'description_op';
  $handler->display->display_options['filters']['description']['expose']['label'] = 'Description';
  $handler->display->display_options['filters']['description']['expose']['use_operator'] = 1;
  $handler->display->display_options['filters']['description']['expose']['operator'] = 'description_op';
  $handler->display->display_options['filters']['description']['expose']['identifier'] = 'description';
  $handler->display->display_options['filters']['description']['expose']['required'] = FALSE;
  $handler->display->display_options['filters']['description']['expose']['multiple'] = FALSE;
  /* Filter criterion: transactions: Payee */
  $handler->display->display_options['filters']['payee']['id'] = 'payee';
  $handler->display->display_options['filters']['payee']['table'] = 'mcapi_transactions';
  $handler->display->display_options['filters']['payee']['field'] = 'payee';
  $handler->display->display_options['filters']['payee']['value'] = '';
  $handler->display->display_options['filters']['payee']['exposed'] = TRUE;
  $handler->display->display_options['filters']['payee']['expose']['operator_id'] = 'payee_op';
  $handler->display->display_options['filters']['payee']['expose']['label'] = 'Payee';
  $handler->display->display_options['filters']['payee']['expose']['use_operator'] = 1;
  $handler->display->display_options['filters']['payee']['expose']['operator'] = 'payee_op';
  $handler->display->display_options['filters']['payee']['expose']['identifier'] = 'payee';
  $handler->display->display_options['filters']['payee']['expose']['multiple'] = FALSE;
  $handler->display->display_options['filters']['payee']['expose']['reduce'] = 0;
  /* Filter criterion: transactions: Payer */
  $handler->display->display_options['filters']['payer']['id'] = 'payer';
  $handler->display->display_options['filters']['payer']['table'] = 'mcapi_transactions';
  $handler->display->display_options['filters']['payer']['field'] = 'payer';
  $handler->display->display_options['filters']['payer']['value'] = '';
  $handler->display->display_options['filters']['payer']['exposed'] = TRUE;
  $handler->display->display_options['filters']['payer']['expose']['operator_id'] = 'payer_op';
  $handler->display->display_options['filters']['payer']['expose']['label'] = 'Payer';
  $handler->display->display_options['filters']['payer']['expose']['use_operator'] = 1;
  $handler->display->display_options['filters']['payer']['expose']['operator'] = 'payer_op';
  $handler->display->display_options['filters']['payer']['expose']['identifier'] = 'payer';
  $handler->display->display_options['filters']['payer']['expose']['multiple'] = FALSE;
  $handler->display->display_options['filters']['payer']['expose']['reduce'] = 0;
  /* Filter criterion: transactions: Timestamp */
  $handler->display->display_options['filters']['created']['id'] = 'created';
  $handler->display->display_options['filters']['created']['table'] = 'mcapi_transactions';
  $handler->display->display_options['filters']['created']['field'] = 'created';
  $handler->display->display_options['filters']['created']['operator'] = '>';
  $handler->display->display_options['filters']['created']['value']['value'] = '1 week';
  $handler->display->display_options['filters']['created']['exposed'] = TRUE;
  $handler->display->display_options['filters']['created']['expose']['operator_id'] = 'created_op';
  $handler->display->display_options['filters']['created']['expose']['label'] = 'Timestamp';
  $handler->display->display_options['filters']['created']['expose']['use_operator'] = 1;
  $handler->display->display_options['filters']['created']['expose']['operator'] = 'created_op';
  $handler->display->display_options['filters']['created']['expose']['identifier'] = 'created';
  $handler->display->display_options['filters']['created']['expose']['multiple'] = FALSE;
  /* Filter criterion: transactions: Type */
  $handler->display->display_options['filters']['type']['id'] = 'type';
  $handler->display->display_options['filters']['type']['table'] = 'mcapi_transactions';
  $handler->display->display_options['filters']['type']['field'] = 'type';
  /* Filter criterion: transactions: transaction state */
  $handler->display->display_options['filters']['state']['id'] = 'state';
  $handler->display->display_options['filters']['state']['table'] = 'mcapi_transactions';
  $handler->display->display_options['filters']['state']['field'] = 'state';
  $handler->display->display_options['filters']['state']['exposed'] = TRUE;
  $handler->display->display_options['filters']['state']['expose']['operator_id'] = '';
  $handler->display->display_options['filters']['state']['expose']['label'] = 'State';
  $handler->display->display_options['filters']['state']['expose']['use_operator'] = FALSE;
  $handler->display->display_options['filters']['state']['expose']['operator'] = 'state_op';
  $handler->display->display_options['filters']['state']['expose']['identifier'] = 'state';
  $handler->display->display_options['filters']['state']['expose']['multiple'] = FALSE;

  /* Display: Page */
  $handler = $view->new_display('page', 'Page', 'page');
  $handler->display->display_options['path'] = 'admin/accounting/transactions';
  $handler->display->display_options['menu']['type'] = 'normal';
  $handler->display->display_options['menu']['title'] = 'Transaction Log';
  $handler->display->display_options['menu']['description'] = 'Filter through all the transactions on the system';
  $handler->display->display_options['menu']['weight'] = '0';
  $handler->display->display_options['menu']['name'] = 'management';
  $translatables['transactions_all'] = array(
    t('All transactions can be seen from here. Use the filters to list what you need.'),
    t('Created'),
    t('Description'),
    t('value'),
    t('Payee'),
    t('Payer'),
    t('Type'),
    t('State'),
    t('Currency ID'),
    t('Timestamp'),
    t('Page'),
  );
  $views[$view->name] = $view;
  return $views;
}
