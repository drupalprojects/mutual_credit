<?php
//$Id:
/**
 * @file
 * Contains default views on behalf of the transactions  module.
 */

function mcapi_views_data() {
  $data = array();
  $data['mcapi_transactions']['table']['group']  = t('transactions');
  $data['mcapi_transactions']['table']['base'] = array(
    'database' => variable_get('mcapi_db_read', 'default'),
    'field' => 'xid',
    'title' => t('transaction'),
    'help' => t('Records of transactions between users'),
    'access query tag' => 'transaction_access',
  );

  $data['mcapi_transactions']['description'] = array(
    'title' => t('Description'), // The item it appears as on the UI,
    'field' => array(
      'help' => t('The user id who spent currency in the transaction.'),
      'handler' => 'views_handler_field_mcapi_description',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
  );
  $data['mcapi_transactions']['payer_uid'] = array(
    'title' => t('Payer'), // The item it appears as on the UI,
    'help' => t('The user who gave currency in the transaction.'),
    'field' => array(
      'handler' => 'views_handler_field_mcapi_trader',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'help' => t('Show only transactions where the specified user paid'),
      'handler' => 'views_handler_filter_user_name'
    ),
    'argument' => array(
      'handler' => 'views_handler_argument'
    ),
  );
  $data['mcapi_transactions']['payee_uid'] = array(
    'title' => t('Payee'),
    'help' => t('The user who received currency in the transaction.'),
    'field' => array(
      'handler' => 'views_handler_field_mcapi_trader',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'help' => t('Show only transactions where the specified user was paid'),
      'handler' => 'views_handler_filter_user_name'
    ),
    'argument' => array(
      'handler' => 'views_handler_argument'
    ),
  );
  $data['mcapi_transactions']['quantity'] = array(
    'title' => t('Amount'), // The item it appears as on the UI,
    'help' => t('Number of units of currency transferred'),
    'field' => array(
      'handler' => 'views_handler_field_mcapi_amount',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'help' => t('transactions above or below a certain value'),
      'handler' => 'views_handler_filter_numeric'
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    )
  );
  $data['mcapi_transactions']['state'] = array(
    'title' => t('State'),
    'help' => t('Whether the transaction is completed (FALSE) or pending (TRUE)'),
    'field' => array(
      'handler' => 'views_handler_field_mcapi_state',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_mcapi_state',
    ),
  );
  $data['mcapi_transactions']['cid'] = array(
    'title' => t('Currency ID'),
    'help' => t('The currency the transaction was denominated in'),
    'field' => array(
      'help' => t("Use this field (check 'exclude') for the theming to work on rating and all quantities in the row"),
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_mcapi_currency',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
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

  $data['mcapi_transactions']['participant'] = array(
    'argument' => array(
      'title' => t('Involved user'),
      'help' => t('Show only those transactions in which a user was involved'),
      'handler' => 'views_handler_argument_mcapi_participant',
    ),
    'field' => array(
      'title' => t('Other user'),
      'help' => t("Show the other user who wasn't passed in the views argument"),
      'handler' => 'views_handler_field_mcapi_other_participant',
      'click sortable' => TRUE
    )
  );
  $data['mcapi_transactions']['created'] = array(
    'title' => t('Timestamp'),
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
  );
  $data['mcapi_transactions']['links'] = array(
    'title' => t('Admin links'),
    'help' => t("Edit and delete links"),
    'field' => array(
      'handler' => 'views_handler_field_transaction_links',
    )
  );

  $data['mcapi_cache']['table']['group'] = t('Balances');
  $data['mcapi_cache']['table']['base'] = array(
    'database' => variable_get('mcapi_db_read', 'default'),
    'field' => 'uid',
    'title' => t('User Balance'),
    'help' => t('Balances of users.'),
  );
  $data['mcapi_cache']['table']['join'] = array(
    'users' => array(
      'left_field' => 'uid',
      'field' => 'uid',
    ),
  );
  $data['mcapi_cache']['uid'] = array(
    'title' => t('User'),
    'argument' => array(
      'help' => t("The user id"),
      'handler' => 'views_handler_argument',
    )
  );
  $data['mcapi_cache']['cid'] = array(
    'title' => t('Currency ID'),
    'field' => array(
      'help' => t("Use this field (check 'exclude') for the theming to work on rating and all quantities in the row"),
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'filter' => array(
       'help' => t('Filter the balances by currency ID'),
       'handler' => 'views_handler_filter_mcapi_currency'
    ),
  );
  $data['mcapi_cache']['cleared_balance'] = array(
    'title' => t('Balance'),
    'field' => array(
      'help' => t("The user's total cleared income minus total cleared expenditure"),
      'handler' => 'views_handler_field_mcapi_amount',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'help' => t("The user's total cleared income minus total cleared expenditure"),
      'handler' => 'views_handler_sort',
    )
  );
  $data['mcapi_cache']['pending_dif'] = array(
    'title' => t('Pending Difference'),
    'field' => array(
      'help' => t("The sum of all pending transactions, (@type)", array('@type'=>'')),
      'handler' => 'views_handler_field_mcapi_amount',
      'click sortable' => TRUE,
    )
  );
  $data['mcapi_cache']['gross_in'] = array(
    'title' => t('Gross Income'),
    'help' => t("The user's total completed income"),
    'field' => array(
      'handler' => 'views_handler_field_mcapi_amount',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    )
  );
  $data['mcapi_cache']['gross_out'] = array(
    'title' => t('Gross Expenditure'),
    'help' => t("The user's total completed expenditure"),
    'field' => array(
      'handler' => 'views_handler_field_mcapi_amount',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    )
  );
  $data['mcapi_cache']['count'] = array(
    'title' => t('Number of transactions'),
    'help' => t('Number of transactions'),
    'field' => array(
      'handler' => 'views_handler_field_mcapi_amount',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    )
  );
  return $data;
}

function mcapi_views_default_views() {
  $view = new view;
  $view->name = 'transactions_all';
  $view->description = 'For admin or accountant to manage transactions';
  $view->tag = 'default';
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
    'description' => 'description',
    'quantity' => 'quantity',
    'cid' => 'cid',
    'description_1' => 'description_1',
    'participant' => 'participant',
    'payee_uid' => 'payee_uid',
    'payer_uid' => 'payer_uid',
    'created' => 'created',
    'type' => 'type',
    'state' => 'state',
  );
  $handler->display->display_options['style_options']['default'] = 'created';
  $handler->display->display_options['style_options']['info'] = array(
    'description' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
    ),
    'quantity' => array(
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
    'payee_uid' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
    ),
    'payer_uid' => array(
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
  /* Field: transactions: Amount */
  $handler->display->display_options['fields']['quantity']['id'] = 'quantity';
  $handler->display->display_options['fields']['quantity']['table'] = 'mcapi_transactions';
  $handler->display->display_options['fields']['quantity']['field'] = 'quantity';
  $handler->display->display_options['fields']['quantity']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['external'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['quantity']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['quantity']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['html'] = 0;
  $handler->display->display_options['fields']['quantity']['element_type_enable'] = 0;
  $handler->display->display_options['fields']['quantity']['element_class_enable'] = 0;
  $handler->display->display_options['fields']['quantity']['element_label_type_enable'] = 0;
  $handler->display->display_options['fields']['quantity']['element_label_class_enable'] = 0;
  $handler->display->display_options['fields']['quantity']['element_label_colon'] = 0;
  $handler->display->display_options['fields']['quantity']['element_wrapper_type_enable'] = 0;
  $handler->display->display_options['fields']['quantity']['element_wrapper_class_enable'] = 0;
  $handler->display->display_options['fields']['quantity']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['quantity']['hide_empty'] = 0;
  $handler->display->display_options['fields']['quantity']['empty_zero'] = 0;
  /* Field: transactions: Payee */
  $handler->display->display_options['fields']['payee_uid']['id'] = 'payee_uid';
  $handler->display->display_options['fields']['payee_uid']['table'] = 'mcapi_transactions';
  $handler->display->display_options['fields']['payee_uid']['field'] = 'payee_uid';
  $handler->display->display_options['fields']['payee_uid']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['payee_uid']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['payee_uid']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['payee_uid']['alter']['external'] = 0;
  $handler->display->display_options['fields']['payee_uid']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['payee_uid']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['payee_uid']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['payee_uid']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['payee_uid']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['payee_uid']['alter']['html'] = 0;
  $handler->display->display_options['fields']['payee_uid']['element_type_enable'] = 0;
  $handler->display->display_options['fields']['payee_uid']['element_class_enable'] = 0;
  $handler->display->display_options['fields']['payee_uid']['element_label_type_enable'] = 0;
  $handler->display->display_options['fields']['payee_uid']['element_label_class_enable'] = 0;
  $handler->display->display_options['fields']['payee_uid']['element_label_colon'] = 0;
  $handler->display->display_options['fields']['payee_uid']['element_wrapper_type_enable'] = 0;
  $handler->display->display_options['fields']['payee_uid']['element_wrapper_class_enable'] = 0;
  $handler->display->display_options['fields']['payee_uid']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['payee_uid']['hide_empty'] = 0;
  $handler->display->display_options['fields']['payee_uid']['empty_zero'] = 0;
  /* Field: transactions: Payer */
  $handler->display->display_options['fields']['payer_uid']['id'] = 'payer_uid';
  $handler->display->display_options['fields']['payer_uid']['table'] = 'mcapi_transactions';
  $handler->display->display_options['fields']['payer_uid']['field'] = 'payer_uid';
  $handler->display->display_options['fields']['payer_uid']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['payer_uid']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['payer_uid']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['payer_uid']['alter']['external'] = 0;
  $handler->display->display_options['fields']['payer_uid']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['payer_uid']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['payer_uid']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['payer_uid']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['payer_uid']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['payer_uid']['alter']['html'] = 0;
  $handler->display->display_options['fields']['payer_uid']['element_type_enable'] = 0;
  $handler->display->display_options['fields']['payer_uid']['element_class_enable'] = 0;
  $handler->display->display_options['fields']['payer_uid']['element_label_type_enable'] = 0;
  $handler->display->display_options['fields']['payer_uid']['element_label_class_enable'] = 0;
  $handler->display->display_options['fields']['payer_uid']['element_label_colon'] = 0;
  $handler->display->display_options['fields']['payer_uid']['element_wrapper_type_enable'] = 0;
  $handler->display->display_options['fields']['payer_uid']['element_wrapper_class_enable'] = 0;
  $handler->display->display_options['fields']['payer_uid']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['payer_uid']['hide_empty'] = 0;
  $handler->display->display_options['fields']['payer_uid']['empty_zero'] = 0;
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
  /* Sort criterion: transactions: Timestamp */
  $handler->display->display_options['sorts']['created']['id'] = 'created';
  $handler->display->display_options['sorts']['created']['table'] = 'mcapi_transactions';
  $handler->display->display_options['sorts']['created']['field'] = 'created';
  $handler->display->display_options['sorts']['created']['order'] = 'DESC';
  $handler->display->display_options['sorts']['created']['granularity'] = 'minute';
  /* Filter criterion: transactions: Amount */
  $handler->display->display_options['filters']['quantity']['id'] = 'quantity';
  $handler->display->display_options['filters']['quantity']['table'] = 'mcapi_transactions';
  $handler->display->display_options['filters']['quantity']['field'] = 'quantity';
  $handler->display->display_options['filters']['quantity']['operator'] = '>';
  $handler->display->display_options['filters']['quantity']['exposed'] = TRUE;
  $handler->display->display_options['filters']['quantity']['expose']['operator_id'] = 'quantity_op';
  $handler->display->display_options['filters']['quantity']['expose']['label'] = 'Amount';
  $handler->display->display_options['filters']['quantity']['expose']['use_operator'] = 1;
  $handler->display->display_options['filters']['quantity']['expose']['operator'] = 'quantity_op';
  $handler->display->display_options['filters']['quantity']['expose']['identifier'] = 'quantity';
  $handler->display->display_options['filters']['quantity']['expose']['multiple'] = FALSE;
  /* Filter criterion: transactions: Currency ID */
  $handler->display->display_options['filters']['cid']['id'] = 'cid';
  $handler->display->display_options['filters']['cid']['table'] = 'mcapi_transactions';
  $handler->display->display_options['filters']['cid']['field'] = 'cid';
  $handler->display->display_options['filters']['cid']['exposed'] = TRUE;
  $handler->display->display_options['filters']['cid']['expose']['operator_id'] = 'cid_op';
  $handler->display->display_options['filters']['cid']['expose']['label'] = 'Currency ID';
  $handler->display->display_options['filters']['cid']['expose']['use_operator'] = TRUE;
  $handler->display->display_options['filters']['cid']['expose']['operator'] = 'cid_op';
  $handler->display->display_options['filters']['cid']['expose']['identifier'] = 'cid';
  $handler->display->display_options['filters']['cid']['expose']['reduce'] = 0;
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
  $handler->display->display_options['filters']['payee_uid']['id'] = 'payee_uid';
  $handler->display->display_options['filters']['payee_uid']['table'] = 'mcapi_transactions';
  $handler->display->display_options['filters']['payee_uid']['field'] = 'payee_uid';
  $handler->display->display_options['filters']['payee_uid']['value'] = '';
  $handler->display->display_options['filters']['payee_uid']['exposed'] = TRUE;
  $handler->display->display_options['filters']['payee_uid']['expose']['operator_id'] = 'payee_uid_op';
  $handler->display->display_options['filters']['payee_uid']['expose']['label'] = 'Payee';
  $handler->display->display_options['filters']['payee_uid']['expose']['use_operator'] = 1;
  $handler->display->display_options['filters']['payee_uid']['expose']['operator'] = 'payee_uid_op';
  $handler->display->display_options['filters']['payee_uid']['expose']['identifier'] = 'payee_uid';
  $handler->display->display_options['filters']['payee_uid']['expose']['multiple'] = FALSE;
  $handler->display->display_options['filters']['payee_uid']['expose']['reduce'] = 0;
  /* Filter criterion: transactions: Payer */
  $handler->display->display_options['filters']['payer_uid']['id'] = 'payer_uid';
  $handler->display->display_options['filters']['payer_uid']['table'] = 'mcapi_transactions';
  $handler->display->display_options['filters']['payer_uid']['field'] = 'payer_uid';
  $handler->display->display_options['filters']['payer_uid']['value'] = '';
  $handler->display->display_options['filters']['payer_uid']['exposed'] = TRUE;
  $handler->display->display_options['filters']['payer_uid']['expose']['operator_id'] = 'payer_uid_op';
  $handler->display->display_options['filters']['payer_uid']['expose']['label'] = 'Payer';
  $handler->display->display_options['filters']['payer_uid']['expose']['operator'] = 'payer_uid_op';
  $handler->display->display_options['filters']['payer_uid']['expose']['identifier'] = 'payer_uid';
  $handler->display->display_options['filters']['payer_uid']['expose']['multiple'] = FALSE;
  $handler->display->display_options['filters']['payer_uid']['expose']['reduce'] = 0;
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
  $handler->display->display_options['filters']['state']['expose']['label'] = 'Pending';
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
    t('Amount'),
    t('Payee'),
    t('Payer'),
    t('Type'),
    t('State'),
    t('Currency ID'),
    t('Timestamp'),
    t('Pending'),
    t('Page'),
  );
  $views[$view->name] = $view;
  return $views;
}

//shorten the user select text boxes on the exposed views form
//this could also be done by css but I'm not creating a file just for that
function mcapi_form_views_exposed_form_alter(&$form, $form_state) {
  $form['payee_uid']['#size'] = 30;
  $form['payer_uid']['#size'] = 30;
}


/**
 * Field handler to provide simple renderer that allows linking to a user.
 */
class views_handler_field_transactions extends views_handler_field {
  /**
   * Override init function to provide generic option to link to user.
   */
  function init(&$view, &$data) {
    parent::init($view, $data);
    if (!empty($this->options['link_to_transaction'])) {
      $this->additional_fields['xid'] = 'xid';
    }
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['link_to_transaction'] = array('default' => TRUE);
    return $options;
  }

  /**
   * Provide link to transaction option
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['link_to_transaction'] = array(
      '#title' => t('Link this field to its transaction'),
      '#description' => t('This will override any other link you have set.'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['link_to_transaction'],
    );
  }

  function render_link($data, $values) {
    if (!empty($this->options['link_to_transaction']) && user_access('access user profiles') && $values->{$this->aliases['xid']} && $data !== NULL && $data !== '') {
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = "transaction/" . $values->{$this->aliases['xid']};
    }
    return $data;
  }

  function render($values) {
    return $this->render_link(check_plain($values->{$this->field_alias}), $values);
  }
}
