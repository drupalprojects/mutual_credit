<?php
// $Id:

function accountant_ui_views_default_views() {
  currencies_load(); //because this seems to run before mcapi_init()
  $currencies = &drupal_static('currencies');
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
  $handler->display->display_options['fields']['worth']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['worth']['element_default_classes'] = 0;
  $handler->display->display_options['fields']['worth']['hide_empty'] = 0;
  $handler->display->display_options['fields']['worth']['empty_zero'] = 0;
  $handler->display->display_options['fields']['worth']['hide_alter_empty'] = 0;
  $handler->display->display_options['fields']['worth']['click_sort_column'] = 'currcode';
  $handler->display->display_options['fields']['worth']['type'] = 'worth_field';
  $handler->display->display_options['fields']['worth']['field_api_classes'] = 0;
  /* Field: Transaction: State */
  $handler->display->display_options['fields']['state']['id'] = 'state';
  $handler->display->display_options['fields']['state']['table'] = 'mcapi_transactions';
  $handler->display->display_options['fields']['state']['field'] = 'state';
  $handler->display->display_options['fields']['state']['element_label_colon'] = 0;
  $handler->display->display_options['fields']['state']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['state']['hide_empty'] = 0;
  $handler->display->display_options['fields']['state']['empty_zero'] = 0;
  /* Field: Transaction: Type */
  $handler->display->display_options['fields']['type']['id'] = 'type';
  $handler->display->display_options['fields']['type']['table'] = 'mcapi_transactions';
  $handler->display->display_options['fields']['type']['field'] = 'type';
  $handler->display->display_options['fields']['type']['element_label_colon'] = 0;
  $handler->display->display_options['fields']['type']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['type']['hide_empty'] = 0;
  $handler->display->display_options['fields']['type']['empty_zero'] = 0;
  /* Field: Transaction: Undo */
  $handler->display->display_options['fields']['undo']['id'] = 'undo';
  $handler->display->display_options['fields']['undo']['table'] = 'mcapi_transactions';
  $handler->display->display_options['fields']['undo']['field'] = 'undo';
  $handler->display->display_options['fields']['undo']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['undo']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['undo']['hide_empty'] = 0;
  $handler->display->display_options['fields']['undo']['empty_zero'] = 0;
  $handler->display->display_options['fields']['undo']['hide_alter_empty'] = 1;
  /* Field: Transaction: View */
  $handler->display->display_options['fields']['view']['id'] = 'view';
  $handler->display->display_options['fields']['view']['table'] = 'mcapi_transactions';
  $handler->display->display_options['fields']['view']['field'] = 'view';
  $handler->display->display_options['fields']['view']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['view']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['view']['hide_empty'] = 0;
  $handler->display->display_options['fields']['view']['empty_zero'] = 0;
  $handler->display->display_options['fields']['view']['hide_alter_empty'] = 1;
  if (module_exists('mcapi_pending')) {
    /* Field: Transaction: Sign off */
    $handler->display->display_options['fields']['signoff']['id'] = 'signoff';
    $handler->display->display_options['fields']['signoff']['table'] = 'mcapi_transactions';
    $handler->display->display_options['fields']['signoff']['field'] = 'signoff';
    $handler->display->display_options['fields']['signoff']['element_label_colon'] = 1;
    $handler->display->display_options['fields']['signoff']['element_default_classes'] = 1;
    $handler->display->display_options['fields']['signoff']['hide_empty'] = 0;
    $handler->display->display_options['fields']['signoff']['empty_zero'] = 0;
    $handler->display->display_options['fields']['signoff']['hide_alter_empty'] = 1;
  }
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
    0 => TRANSACTION_STATE_FINISHED,
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

  if (count($currencies) > 1) {
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
  $handler->display->display_options['menu']['title'] = 'Manage transactions';
  $handler->display->display_options['menu']['description'] = "This is the default accountant's interface. It is simply a transaction view with all the filters exposed. More accountants tools are needed, depending on how the system is to be used.";
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
    t('Value'),
    t('State'),
    t('Link'),
    t('view'),
    t('Type'),
    t('Quantity'),
    t('Page'),
  );

  $views[$view->name] = $view;
  return $views;
}