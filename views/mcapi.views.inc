<?php
//$Id:
/**
 * @file
 * Contains default views on behalf of the exchanges  module.
 */

function mcapi_views_data() {
  $data = array();
  $data['mcapi_exchanges']['table']['group']  = t('Exchanges');
  $data['mcapi_exchanges']['table']['base'] = array(
    'field' => 'xid',
    'title' => t('Exchange'),
    'help' => t('Records of exchanges between users'),
  );

  $data['mcapi_exchanges']['description'] = array(
    'title' => t('Description'), // The item it appears as on the UI,
    'field' => array(
      'help' => t('The user id who spent currency in the exchange.'),
      'handler' => 'views_handler_field_node',
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
  $data['mcapi_exchanges']['payer_uid'] = array(
    'title' => t('Payer'), // The item it appears as on the UI,
    'help' => t('The user who gave currency in the exchange.'),
    'field' => array(
      'handler' => 'views_handler_field_mcapi_trader',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'help' => t('Show only exchanges where the specified user paid'),
      'handler' => 'views_handler_filter_user_name'
    ),
    'argument' => array(
      'handler' => 'views_handler_argument'
    ),
  );
  $data['mcapi_exchanges']['payee_uid'] = array(
    'title' => t('Payee'),
    'help' => t('The user who received currency in the exchange.'),
    'field' => array(
      'handler' => 'views_handler_field_mcapi_trader',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'help' => t('Show only exchanges where the specified user was paid'),
      'handler' => 'views_handler_filter_user_name'
    ),
    'argument' => array(
      'handler' => 'views_handler_argument'
    ),
  );
  $data['mcapi_exchanges']['quantity'] = array(
    'title' => t('Amount'), // The item it appears as on the UI,
    'help' => t('Number of units of currency transferred'),
    'field' => array(
      'handler' => 'views_handler_field_mcapi_amount',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'help' => t('Exchanges above or below a certain value'),
      'handler' => 'views_handler_filter_numeric'
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    )
  );
  $data['mcapi_exchanges']['state'] = array(
    'title' => t('Exchange state'),
    'help' => t('Whether the exchange is completed (FALSE) or pending (TRUE)'),
    'field' => array(
      'handler' => 'views_handler_field_boolean',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_boolean_operator',
    ),
  );
  $data['mcapi_exchanges']['cid'] = array(
    'title' => t('Currency ID'),
    'help' => t('The currency the exchange was denominated in'),
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

  $data['mcapi_exchanges']['type'] = array(
    'title' => t('Type'),
    'help' => t('Which form or module which created the exchange'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_mcapi_types',
    ),
  );

  $data['mcapi_exchanges']['participant'] = array(
    'argument' => array(
      'title' => t('Involved user'),
      'help' => t('Show only those exchanges in which a user was involved'),
      'handler' => 'views_handler_argument_mcapi_participant',
    ),
    'field' => array(
      'title' => t('Other user'),
      'help' => t("Show the other user who wasn't passed in the views argument"),
      'handler' => 'views_handler_field_mcapi_other_participant',
      'click sortable' => TRUE
    )
  );
  $data['mcapi_exchanges']['created'] = array(
    'title' => t('Timestamp'),
    'help' => t("the second the exchange was created"),
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


  $data['mcapi_cache']['table']['group'] = t('Balances');
  $data['mcapi_cache']['table']['base'] = array(
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
      'help' => t("The sum of all pending exchanges, (@type)", array('@type'=>'')),
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
    'title' => t('Number of exchanges'),
    'help' => t('Number of exchanges'),
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
  $view = mcapi_exchanges_all_view();
  $views[$view->name] = $view;
  $view = mcapi_exchanges_user_view();
  $views[$view->name] = $view;
  return $views;
}

function mcapi_exchanges_all_view() {
  $view = new view;
  $view->name = 'mcapi_exchanges';
  $view->description = 'list of exchanges';
  $view->tag = 'exchanges';
  $view->base_table = 'mcapi_exchanges';
  $view->human_name = '';
  $view->core = 0;
  $view->api_version = '3.0-alpha1';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Defaults */
  $handler = $view->new_display('default', 'Defaults', 'default');
  $handler->display->display_options['title'] = 'Exchange list';
  $handler->display->display_options['access']['type'] = 'perm';
  $handler->display->display_options['access']['perm'] = 'view all exchanges';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'mini';
  $handler->display->display_options['pager']['options']['items_per_page'] = 25;
  $handler->display->display_options['style_plugin'] = 'table';
  $handler->display->display_options['style_options']['columns'] = array(
    'payer_uid' => 'payer_uid',
    'payee_uid' => 'payee_uid',
    'quantity' => 'quantity',
  );
  $handler->display->display_options['style_options']['default'] = 'changed';
  $handler->display->display_options['style_options']['info'] = array(
    'changed' => array(
      'sortable' => 1,
      'separator' => '',
    ),
    'title' => array(
      'sortable' => 0,
      'separator' => '',
    ),
    'payer_uid' => array(
      'separator' => '',
    ),
    'payee_uid' => array(
      'separator' => '',
    ),
    'quantity' => array(
      'sortable' => 1,
      'separator' => '',
    ),
    'rating' => array(
      'sortable' => 0,
      'separator' => '',
    ),
  );
  $handler->display->display_options['style_options']['override'] = 1;
  $handler->display->display_options['style_options']['sticky'] = 0;
  $handler->display->display_options['style_options']['order'] = 'desc';
  /* Empty text: Global: Text area */
  $handler->display->display_options['empty']['text']['id'] = 'area';
  $handler->display->display_options['empty']['text']['table'] = 'views';
  $handler->display->display_options['empty']['text']['field'] = 'area';
  $handler->display->display_options['empty']['text']['empty'] = FALSE;
  $handler->display->display_options['empty']['text']['content'] = 'There are no exchanges in the system yet';
  $handler->display->display_options['empty']['text']['format'] = '1';
  /* Field: Exchanges: Payer */
  $handler->display->display_options['fields']['payer_uid']['id'] = 'payer_uid';
  $handler->display->display_options['fields']['payer_uid']['table'] = 'mcapi_exchanges';
  $handler->display->display_options['fields']['payer_uid']['field'] = 'payer_uid';
  $handler->display->display_options['fields']['payer_uid']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['payer_uid']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['payer_uid']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['payer_uid']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['payer_uid']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['payer_uid']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['payer_uid']['alter']['html'] = 0;
  $handler->display->display_options['fields']['payer_uid']['hide_empty'] = 0;
  $handler->display->display_options['fields']['payer_uid']['empty_zero'] = 0;
  /* Field: Exchanges: Payee */
  $handler->display->display_options['fields']['payee_uid']['id'] = 'payee_uid';
  $handler->display->display_options['fields']['payee_uid']['table'] = 'mcapi_exchanges';
  $handler->display->display_options['fields']['payee_uid']['field'] = 'payee_uid';
  $handler->display->display_options['fields']['payee_uid']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['payee_uid']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['payee_uid']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['payee_uid']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['payee_uid']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['payee_uid']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['payee_uid']['alter']['html'] = 0;
  $handler->display->display_options['fields']['payee_uid']['hide_empty'] = 0;
  $handler->display->display_options['fields']['payee_uid']['empty_zero'] = 0;
  /* Field: Exchanges: Amount */
  $handler->display->display_options['fields']['quantity']['id'] = 'quantity';
  $handler->display->display_options['fields']['quantity']['table'] = 'mcapi_exchanges';
  $handler->display->display_options['fields']['quantity']['field'] = 'quantity';
  /* Sort criterion: Node: Post date */
  $handler->display->display_options['sorts']['created']['id'] = 'created';
  $handler->display->display_options['sorts']['created']['table'] = 'node';
  $handler->display->display_options['sorts']['created']['field'] = 'created';
  $handler->display->display_options['sorts']['created']['order'] = 'DESC';
  $handler->display->display_options['sorts']['created']['granularity'] = 'hour';

  /* Display: Member exchanges */
  $handler = $view->new_display('page', 'Member exchanges', 'page_1');
  $handler->display->display_options['defaults']['header'] = FALSE;
  /* Header: Global: Text area */
  $handler->display->display_options['header']['text']['id'] = 'area';
  $handler->display->display_options['header']['text']['table'] = 'views';
  $handler->display->display_options['header']['text']['field'] = 'area';
  $handler->display->display_options['header']['text']['empty'] = FALSE;
  $handler->display->display_options['header']['text']['content'] = 'all the exchanges on the system, except the mass-exchanges, and those involving admin';
  $handler->display->display_options['header']['text']['format'] = '1';
  $handler->display->display_options['defaults']['filters'] = FALSE;
  /* Filter: Exchanges: Type */
  $handler->display->display_options['filters']['type']['id'] = 'type';
  $handler->display->display_options['filters']['type']['table'] = 'mcapi_exchanges';
  $handler->display->display_options['filters']['type']['field'] = 'type';
  $handler->display->display_options['filters']['type']['operator'] = 'in';
  $handler->display->display_options['filters']['type']['value'] = array(
    'exchange' => 'exchange',
  );
  $handler->display->display_options['filters']['type']['expose']['operator'] = FALSE;
  /* Filter: Exchanges: Payee */
  $handler->display->display_options['filters']['payee_uid_1']['id'] = 'payee_uid_1';
  $handler->display->display_options['filters']['payee_uid_1']['table'] = 'mcapi_exchanges';
  $handler->display->display_options['filters']['payee_uid_1']['field'] = 'payee_uid';
  $handler->display->display_options['filters']['payee_uid_1']['value'] = '';
  $handler->display->display_options['filters']['payee_uid_1']['exposed'] = TRUE;
  $handler->display->display_options['filters']['payee_uid_1']['expose']['operator'] = 'payee_uid_1_op';
  $handler->display->display_options['filters']['payee_uid_1']['expose']['label'] = 'Payee';
  $handler->display->display_options['filters']['payee_uid_1']['expose']['identifier'] = 'payee_uid_1';
  $handler->display->display_options['filters']['payee_uid_1']['expose']['reduce'] = 1;
  /* Filter: Exchanges: Payer */
  $handler->display->display_options['filters']['payer_uid']['id'] = 'payer_uid';
  $handler->display->display_options['filters']['payer_uid']['table'] = 'mcapi_exchanges';
  $handler->display->display_options['filters']['payer_uid']['field'] = 'payer_uid';
  $handler->display->display_options['filters']['payer_uid']['value'] = '';
  $handler->display->display_options['filters']['payer_uid']['exposed'] = TRUE;
  $handler->display->display_options['filters']['payer_uid']['expose']['operator'] = 'payer_uid_op';
  $handler->display->display_options['filters']['payer_uid']['expose']['label'] = 'Payer';
  $handler->display->display_options['filters']['payer_uid']['expose']['identifier'] = 'payer_uid';
  $handler->display->display_options['filters']['payer_uid']['expose']['reduce'] = 1;
  $handler->display->display_options['path'] = 'exchanges/list';
  $handler->display->display_options['menu']['type'] = 'default tab';
  $handler->display->display_options['menu']['title'] = 'All exchanges';
  $handler->display->display_options['menu']['description'] = 'All member-to-member exchanges';
  $handler->display->display_options['menu']['weight'] = '20';
  $handler->display->display_options['tab_options']['type'] = 'normal';
  $handler->display->display_options['tab_options']['title'] = 'Exchanges';
  $handler->display->display_options['tab_options']['weight'] = '0';

  /* Display: Filter exchanges */
  $handler = $view->new_display('page', 'Filter exchanges', 'page_2');
  $handler->display->display_options['defaults']['style_plugin'] = FALSE;
  $handler->display->display_options['style_plugin'] = 'default';
  $handler->display->display_options['defaults']['style_options'] = FALSE;
  $handler->display->display_options['defaults']['row_plugin'] = FALSE;
  $handler->display->display_options['row_plugin'] = 'fields';
  $handler->display->display_options['defaults']['row_options'] = FALSE;
  $handler->display->display_options['defaults']['header'] = FALSE;
  /* Header: Global: Text area */
  $handler->display->display_options['header']['text']['id'] = 'area';
  $handler->display->display_options['header']['text']['table'] = 'views';
  $handler->display->display_options['header']['text']['field'] = 'area';
  $handler->display->display_options['header']['text']['empty'] = FALSE;
  $handler->display->display_options['header']['text']['content'] = 'Use the widgets to filter through all the exchanges ever listed, and then sort the columns by clicking the column headings';
  $handler->display->display_options['header']['text']['format'] = '1';
  $handler->display->display_options['defaults']['empty'] = FALSE;
  /* Empty text: Global: Text area */
  $handler->display->display_options['empty']['text']['id'] = 'area';
  $handler->display->display_options['empty']['text']['table'] = 'views';
  $handler->display->display_options['empty']['text']['field'] = 'area';
  $handler->display->display_options['empty']['text']['empty'] = FALSE;
  $handler->display->display_options['empty']['text']['content'] = 'No exchanges meet these criteria. Try using fewer filters';
  $handler->display->display_options['defaults']['filters'] = FALSE;
  /* Filter: Exchanges: Type */
  $handler->display->display_options['filters']['type']['id'] = 'type';
  $handler->display->display_options['filters']['type']['table'] = 'mcapi_exchanges';
  $handler->display->display_options['filters']['type']['field'] = 'type';
  $handler->display->display_options['filters']['type']['operator'] = 'in';
  $handler->display->display_options['filters']['type']['value'] = array(
    'exchange' => 'exchange',
  );
  $handler->display->display_options['filters']['type']['expose']['operator'] = FALSE;
  /* Filter: Exchanges: Payee */
  $handler->display->display_options['filters']['payee_uid_1']['id'] = 'payee_uid_1';
  $handler->display->display_options['filters']['payee_uid_1']['table'] = 'mcapi_exchanges';
  $handler->display->display_options['filters']['payee_uid_1']['field'] = 'payee_uid';
  $handler->display->display_options['filters']['payee_uid_1']['value'] = '';
  $handler->display->display_options['filters']['payee_uid_1']['exposed'] = TRUE;
  $handler->display->display_options['filters']['payee_uid_1']['expose']['operator'] = 'payee_uid_1_op';
  $handler->display->display_options['filters']['payee_uid_1']['expose']['label'] = 'Payee';
  $handler->display->display_options['filters']['payee_uid_1']['expose']['identifier'] = 'payee_uid_1';
  $handler->display->display_options['filters']['payee_uid_1']['expose']['reduce'] = 0;
  /* Filter: Exchanges: Payer */
  $handler->display->display_options['filters']['payer_uid']['id'] = 'payer_uid';
  $handler->display->display_options['filters']['payer_uid']['table'] = 'mcapi_exchanges';
  $handler->display->display_options['filters']['payer_uid']['field'] = 'payer_uid';
  $handler->display->display_options['filters']['payer_uid']['value'] = '';
  $handler->display->display_options['filters']['payer_uid']['exposed'] = TRUE;
  $handler->display->display_options['filters']['payer_uid']['expose']['operator'] = 'payer_uid_op';
  $handler->display->display_options['filters']['payer_uid']['expose']['label'] = 'Payer';
  $handler->display->display_options['filters']['payer_uid']['expose']['identifier'] = 'payer_uid';
  $handler->display->display_options['filters']['payer_uid']['expose']['reduce'] = 0;
  /* Filter: Exchanges: Exchange state */
  $handler->display->display_options['filters']['state']['id'] = 'state';
  $handler->display->display_options['filters']['state']['table'] = 'mcapi_exchanges';
  $handler->display->display_options['filters']['state']['field'] = 'state';
  $handler->display->display_options['filters']['state']['value'] = 'All';
  $handler->display->display_options['filters']['state']['exposed'] = TRUE;
  $handler->display->display_options['filters']['state']['expose']['label'] = 'Pending';
  $handler->display->display_options['filters']['state']['expose']['identifier'] = 'state';
  /* Filter: Exchanges: Type */
  $handler->display->display_options['filters']['type_1']['id'] = 'type_1';
  $handler->display->display_options['filters']['type_1']['table'] = 'mcapi_exchanges';
  $handler->display->display_options['filters']['type_1']['field'] = 'type';
  $handler->display->display_options['filters']['type_1']['exposed'] = TRUE;
  $handler->display->display_options['filters']['type_1']['expose']['operator'] = 'type_1_op';
  $handler->display->display_options['filters']['type_1']['expose']['label'] = 'Exchanges: Type';
  $handler->display->display_options['filters']['type_1']['expose']['identifier'] = 'type_1';
  $handler->display->display_options['filters']['type_1']['expose']['remember'] = 1;
  $handler->display->display_options['filters']['type_1']['expose']['single'] = 0;
  $handler->display->display_options['filters']['type_1']['expose']['reduce'] = 0;
  $handler->display->display_options['filters']['type_1']['reduce_duplicates'] = 0;
  $handler->display->display_options['path'] = 'exchanges/filter';
  $handler->display->display_options['menu']['type'] = 'tab';
  $handler->display->display_options['menu']['title'] = 'Filtered exchanges';
  $handler->display->display_options['menu']['weight'] = '25';

  /* Display: who gave to whom */
  $handler = $view->new_display('block', 'who gave to whom', 'block_1');
  $handler->display->display_options['defaults']['items_per_page'] = FALSE;
  $handler->display->display_options['defaults']['style_plugin'] = FALSE;
  $handler->display->display_options['style_plugin'] = 'list';
  $handler->display->display_options['defaults']['style_options'] = FALSE;
  $handler->display->display_options['defaults']['row_plugin'] = FALSE;
  $handler->display->display_options['row_plugin'] = 'fields';
  $handler->display->display_options['row_options']['inline'] = array(
    'payer_uid' => 'payer_uid',
    'quantity' => 'quantity',
    'payee_uid' => 'payee_uid',
  );
  $handler->display->display_options['row_options']['hide_empty'] = 0;
  $handler->display->display_options['defaults']['row_options'] = FALSE;
  $handler->display->display_options['defaults']['fields'] = FALSE;
  /* Field: Exchanges: Payer */
  $handler->display->display_options['fields']['payer_uid']['id'] = 'payer_uid';
  $handler->display->display_options['fields']['payer_uid']['table'] = 'mcapi_exchanges';
  $handler->display->display_options['fields']['payer_uid']['field'] = 'payer_uid';
  $handler->display->display_options['fields']['payer_uid']['label'] = '';
  $handler->display->display_options['fields']['payer_uid']['exclude'] = TRUE;
  $handler->display->display_options['fields']['payer_uid']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['payer_uid']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['payer_uid']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['payer_uid']['alter']['word_boundary'] = 0;
  $handler->display->display_options['fields']['payer_uid']['alter']['ellipsis'] = 0;
  $handler->display->display_options['fields']['payer_uid']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['payer_uid']['alter']['html'] = 0;
  $handler->display->display_options['fields']['payer_uid']['hide_empty'] = 0;
  $handler->display->display_options['fields']['payer_uid']['empty_zero'] = 0;
  /* Field: Exchanges: Payee */
  $handler->display->display_options['fields']['payee_uid']['id'] = 'payee_uid';
  $handler->display->display_options['fields']['payee_uid']['table'] = 'mcapi_exchanges';
  $handler->display->display_options['fields']['payee_uid']['field'] = 'payee_uid';
  $handler->display->display_options['fields']['payee_uid']['label'] = '';
  $handler->display->display_options['fields']['payee_uid']['exclude'] = TRUE;
  $handler->display->display_options['fields']['payee_uid']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['payee_uid']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['payee_uid']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['payee_uid']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['payee_uid']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['payee_uid']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['payee_uid']['alter']['html'] = 0;
  $handler->display->display_options['fields']['payee_uid']['hide_empty'] = 0;
  $handler->display->display_options['fields']['payee_uid']['empty_zero'] = 0;
  /* Field: Exchanges: Amount */
  $handler->display->display_options['fields']['quantity']['id'] = 'quantity';
  $handler->display->display_options['fields']['quantity']['table'] = 'mcapi_exchanges';
  $handler->display->display_options['fields']['quantity']['field'] = 'quantity';
  $handler->display->display_options['fields']['quantity']['label'] = '';
  $handler->display->display_options['fields']['quantity']['alter']['alter_text'] = 1;
  $handler->display->display_options['fields']['quantity']['alter']['text'] = '[payer_uid] gave [quantity] to [payee_uid]';
  $handler->display->display_options['fields']['quantity']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['quantity']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['quantity']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['html'] = 0;
  $handler->display->display_options['fields']['quantity']['hide_empty'] = 0;
  $handler->display->display_options['fields']['quantity']['empty_zero'] = 0;

  /* Display: exchanging what? */
  $handler = $view->new_display('block', 'exchanging what?', 'block_2');
  $handler->display->display_options['defaults']['items_per_page'] = FALSE;
  $handler->display->display_options['defaults']['access'] = FALSE;
  $handler->display->display_options['access']['type'] = 'none';
  $handler->display->display_options['defaults']['style_plugin'] = FALSE;
  $handler->display->display_options['style_plugin'] = 'default';
  $handler->display->display_options['defaults']['style_options'] = FALSE;
  $handler->display->display_options['defaults']['row_plugin'] = FALSE;
  $handler->display->display_options['row_plugin'] = 'fields';
  $handler->display->display_options['row_options']['inline'] = array(
    'title' => 'title',
    'quantity' => 'quantity',
  );
  $handler->display->display_options['row_options']['separator'] = ':';
  $handler->display->display_options['row_options']['hide_empty'] = 0;
  $handler->display->display_options['defaults']['row_options'] = FALSE;
  $handler->display->display_options['defaults']['fields'] = FALSE;
  /* Field: Exchanges: Description */
  $handler->display->display_options['fields']['description']['id'] = 'description';
  $handler->display->display_options['fields']['description']['table'] = 'mcapi_exchanges';
  $handler->display->display_options['fields']['description']['field'] = 'description';
  $handler->display->display_options['fields']['description']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['description']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['description']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['description']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['description']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['description']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['description']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['description']['alter']['html'] = 0;
  $handler->display->display_options['fields']['description']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['description']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['description']['hide_empty'] = 0;
  $handler->display->display_options['fields']['description']['empty_zero'] = 0;
  /* Field: Exchanges: Amount */
  $handler->display->display_options['fields']['quantity']['id'] = 'quantity';
  $handler->display->display_options['fields']['quantity']['table'] = 'mcapi_exchanges';
  $handler->display->display_options['fields']['quantity']['field'] = 'quantity';
  $handler->display->display_options['fields']['quantity']['label'] = '';
  $handler->display->display_options['fields']['quantity']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['text'] = ':[quantity]';
  $handler->display->display_options['fields']['quantity']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['quantity']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['quantity']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['html'] = 0;
  $handler->display->display_options['fields']['quantity']['hide_empty'] = 0;
  $handler->display->display_options['fields']['quantity']['empty_zero'] = 0;
  $translatables['mcapi_exchanges'] = array(
    t('Defaults'),
    t('Exchange list'),
    t('more'),
    t('Apply'),
    t('Reset'),
    t('Sort By'),
    t('Asc'),
    t('Desc'),
    t('Items per page'),
    t('- All -'),
    t('Offset'),
    t('There are no exchanges in the system yet'),
    t('Payer'),
    t('Payee'),
    t('Amount'),
    t('Member exchanges'),
    t('all the exchanges on the system, except the mass-exchanges, and those involving admin'),
    t('Filter exchanges'),
    t('Use the widgets to filter through all the exchanges ever listed, and then sort the columns by clicking the column headings'),
    t('No exchanges meet these criteria. Try using fewer filters'),
    t('Pending'),
    t('Exchanges: Type'),
    t('who gave to whom'),
    t('[payer_uid] gave [quantity] to [payee_uid]'),
    t('exchanging what?'),
    t('Description'),
    t(':[quantity]'),
  );
  return $view;
}


function mcapi_exchanges_user_view() {
  $view = new view;
  $view->name = 'mcapi_exchanges_user';
  $view->description = 'exchanges of one user';
  $view->tag = 'exchanges';
  $view->base_table = 'mcapi_exchanges';
  $view->human_name = '';
  $view->core = 0;
  $view->api_version = '3.0-alpha1';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Defaults */
  $handler = $view->new_display('default', 'Defaults', 'default');
  $handler->display->display_options['title'] = 'Exchange list';
  $handler->display->display_options['access']['type'] = 'perm';
  $handler->display->display_options['access']['perm'] = 'view all exchanges';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'mini';
  $handler->display->display_options['pager']['options']['items_per_page'] = 25;
  $handler->display->display_options['style_plugin'] = 'table';
  $handler->display->display_options['style_options']['columns'] = array(
    'changed' => 'changed',
    'title' => 'title',
    'payer_uid' => 'payer_uid',
    'payee_uid' => 'payee_uid',
    'quantity' => 'quantity',
    'rating' => 'rating',
    'quantity_1' => 'quantity_1',
    'quantity_2' => 'quantity_2',
    'quantity_3' => 'quantity_3',
    'participant' => 'participant',
  );
  $handler->display->display_options['style_options']['default'] = 'changed';
  $handler->display->display_options['style_options']['info'] = array(
    'changed' => array(
      'sortable' => 1,
      'separator' => '',
    ),
    'title' => array(
      'sortable' => 0,
      'separator' => '',
    ),
    'payer_uid' => array(
      'sortable' => 1,
      'separator' => '',
    ),
    'payee_uid' => array(
      'sortable' => 1,
      'separator' => '',
    ),
    'quantity' => array(
      'sortable' => 1,
      'separator' => '',
    ),
    'rating' => array(
      'sortable' => 1,
      'separator' => '',
    ),
    'quantity_1' => array(
      'sortable' => 1,
      'separator' => '',
    ),
    'quantity_2' => array(
      'sortable' => 1,
      'separator' => '',
    ),
    'quantity_3' => array(
      'sortable' => 0,
      'separator' => '',
    ),
    'participant' => array(
      'sortable' => 1,
      'separator' => '',
    ),
  );
  $handler->display->display_options['style_options']['override'] = 1;
  $handler->display->display_options['style_options']['sticky'] = 0;
  $handler->display->display_options['style_options']['order'] = 'desc';
  /* Footer: Global: Text area */
  $handler->display->display_options['footer']['text']['id'] = 'area';
  $handler->display->display_options['footer']['text']['table'] = 'views';
  $handler->display->display_options['footer']['text']['field'] = 'area';
  $handler->display->display_options['footer']['text']['empty'] = TRUE;
  $handler->display->display_options['footer']['text']['content'] = l('Add a new exchange', 'exchange');
  $handler->display->display_options['footer']['text']['format'] = '1';
  /* Empty text: Global: Text area */
  $handler->display->display_options['empty']['text']['id'] = 'area';
  $handler->display->display_options['empty']['text']['table'] = 'views';
  $handler->display->display_options['empty']['text']['field'] = 'area';
  $handler->display->display_options['empty']['text']['empty'] = FALSE;
  $handler->display->display_options['empty']['text']['content'] = 'There are no exchanges in the system yet';
  $handler->display->display_options['empty']['text']['format'] = '1';
  /* Field: Exchanges: Timestamp */
  $handler->display->display_options['fields']['created']['id'] = 'created';
  $handler->display->display_options['fields']['created']['table'] = 'mcapi_exchanges';
  $handler->display->display_options['fields']['created']['field'] = 'created';
  $handler->display->display_options['fields']['created']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['created']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['created']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['created']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['created']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['created']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['created']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['created']['alter']['html'] = 0;
  $handler->display->display_options['fields']['created']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['created']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['created']['hide_empty'] = 0;
  $handler->display->display_options['fields']['created']['empty_zero'] = 0;
  /* Field: Exchanges: Description */
  $handler->display->display_options['fields']['description']['id'] = 'description';
  $handler->display->display_options['fields']['description']['table'] = 'mcapi_exchanges';
  $handler->display->display_options['fields']['description']['field'] = 'description';
  $handler->display->display_options['fields']['description']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['description']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['description']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['description']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['description']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['description']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['description']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['description']['alter']['html'] = 0;
  $handler->display->display_options['fields']['description']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['description']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['description']['hide_empty'] = 0;
  $handler->display->display_options['fields']['description']['empty_zero'] = 0;
  /* Field: Exchanges: Payer */
  $handler->display->display_options['fields']['payer_uid']['id'] = 'payer_uid';
  $handler->display->display_options['fields']['payer_uid']['table'] = 'mcapi_exchanges';
  $handler->display->display_options['fields']['payer_uid']['field'] = 'payer_uid';
  $handler->display->display_options['fields']['payer_uid']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['payer_uid']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['payer_uid']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['payer_uid']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['payer_uid']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['payer_uid']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['payer_uid']['alter']['html'] = 0;
  $handler->display->display_options['fields']['payer_uid']['hide_empty'] = 0;
  $handler->display->display_options['fields']['payer_uid']['empty_zero'] = 0;
  /* Field: Exchanges: Payee */
  $handler->display->display_options['fields']['payee_uid']['id'] = 'payee_uid';
  $handler->display->display_options['fields']['payee_uid']['table'] = 'mcapi_exchanges';
  $handler->display->display_options['fields']['payee_uid']['field'] = 'payee_uid';
  $handler->display->display_options['fields']['payee_uid']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['payee_uid']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['payee_uid']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['payee_uid']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['payee_uid']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['payee_uid']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['payee_uid']['alter']['html'] = 0;
  $handler->display->display_options['fields']['payee_uid']['hide_empty'] = 0;
  $handler->display->display_options['fields']['payee_uid']['empty_zero'] = 0;
  /* Field: Exchanges: Amount */
  $handler->display->display_options['fields']['quantity']['id'] = 'quantity';
  $handler->display->display_options['fields']['quantity']['table'] = 'mcapi_exchanges';
  $handler->display->display_options['fields']['quantity']['field'] = 'quantity';
  $handler->display->display_options['fields']['quantity']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['quantity']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['quantity']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['quantity']['alter']['html'] = 0;
  $handler->display->display_options['fields']['quantity']['hide_empty'] = 0;
  $handler->display->display_options['fields']['quantity']['empty_zero'] = 0;
  /* Field: Exchanges: Amount */
  $handler->display->display_options['fields']['quantity_1']['id'] = 'quantity_1';
  $handler->display->display_options['fields']['quantity_1']['table'] = 'mcapi_exchanges';
  $handler->display->display_options['fields']['quantity_1']['field'] = 'quantity';
  $handler->display->display_options['fields']['quantity_1']['label'] = 'incoming';
  $handler->display->display_options['fields']['quantity_1']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['quantity_1']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['quantity_1']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['quantity_1']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['quantity_1']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['quantity_1']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['quantity_1']['alter']['html'] = 0;
  $handler->display->display_options['fields']['quantity_1']['hide_empty'] = 0;
  $handler->display->display_options['fields']['quantity_1']['empty_zero'] = 0;
  $handler->display->display_options['fields']['quantity_1']['output_type'] = 'incoming';
  /* Field: Exchanges: Amount */
  $handler->display->display_options['fields']['quantity_2']['id'] = 'quantity_2';
  $handler->display->display_options['fields']['quantity_2']['table'] = 'mcapi_exchanges';
  $handler->display->display_options['fields']['quantity_2']['field'] = 'quantity';
  $handler->display->display_options['fields']['quantity_2']['label'] = 'outgoing';
  $handler->display->display_options['fields']['quantity_2']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['quantity_2']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['quantity_2']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['quantity_2']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['quantity_2']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['quantity_2']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['quantity_2']['alter']['html'] = 0;
  $handler->display->display_options['fields']['quantity_2']['hide_empty'] = 0;
  $handler->display->display_options['fields']['quantity_2']['empty_zero'] = 0;
  $handler->display->display_options['fields']['quantity_2']['output_type'] = 'outgoing';
  /* Field: Exchanges: Amount */
  $handler->display->display_options['fields']['quantity_3']['id'] = 'quantity_3';
  $handler->display->display_options['fields']['quantity_3']['table'] = 'mcapi_exchanges';
  $handler->display->display_options['fields']['quantity_3']['field'] = 'quantity';
  $handler->display->display_options['fields']['quantity_3']['label'] = 'Change';
  $handler->display->display_options['fields']['quantity_3']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['quantity_3']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['quantity_3']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['quantity_3']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['quantity_3']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['quantity_3']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['quantity_3']['alter']['html'] = 0;
  $handler->display->display_options['fields']['quantity_3']['hide_empty'] = 0;
  $handler->display->display_options['fields']['quantity_3']['empty_zero'] = 0;
  $handler->display->display_options['fields']['quantity_3']['output_type'] = 'sign';
  /* Field: Exchanges: Other user */
  $handler->display->display_options['fields']['participant']['id'] = 'participant';
  $handler->display->display_options['fields']['participant']['table'] = 'mcapi_exchanges';
  $handler->display->display_options['fields']['participant']['field'] = 'participant';
  $handler->display->display_options['fields']['participant']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['participant']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['participant']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['participant']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['participant']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['participant']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['participant']['alter']['html'] = 0;
  $handler->display->display_options['fields']['participant']['hide_empty'] = 0;
  $handler->display->display_options['fields']['participant']['empty_zero'] = 0;
  /* Argument: Exchanges: Involved user */
  $handler->display->display_options['arguments']['participant']['id'] = 'participant';
  $handler->display->display_options['arguments']['participant']['table'] = 'mcapi_exchanges';
  $handler->display->display_options['arguments']['participant']['field'] = 'participant';
  $handler->display->display_options['arguments']['participant']['default_action'] = 'default';
  $handler->display->display_options['arguments']['participant']['style_plugin'] = 'default_summary';
  $handler->display->display_options['arguments']['participant']['default_argument_type'] = 'current_user';
  $handler->display->display_options['arguments']['participant']['validate_type'] = 'user';
  $handler->display->display_options['arguments']['participant']['validate_options']['restrict_roles'] = 0;
  $handler->display->display_options['arguments']['participant']['validate_options']['roles'] = array(
    2 => 0,
  );
  $handler->display->display_options['arguments']['participant']['break_phrase'] = 0;
  $handler->display->display_options['arguments']['participant']['not'] = 0;

  /* Display: All exchanges */
  $handler = $view->new_display('page', 'All exchanges', 'page_1');
  $handler->display->display_options['defaults']['header'] = FALSE;
  /* Header: Global: Text area */
  $handler->display->display_options['header']['text']['id'] = 'area';
  $handler->display->display_options['header']['text']['table'] = 'views';
  $handler->display->display_options['header']['text']['field'] = 'area';
  $handler->display->display_options['header']['text']['empty'] = FALSE;
  $handler->display->display_options['header']['text']['content'] = 'Click the column headings to re-sort';
  $handler->display->display_options['header']['text']['format'] = '1';
  $handler->display->display_options['path'] = 'user/%/exchanges';
  $handler->display->display_options['menu']['type'] = 'tab';
  $handler->display->display_options['menu']['title'] = 'Exchanges';
  $handler->display->display_options['menu']['description'] = 'Exchanges in which this user participated';
  $handler->display->display_options['menu']['weight'] = '3';
  $handler->display->display_options['tab_options']['type'] = 'normal';
  $handler->display->display_options['tab_options']['title'] = 'Exchanges';
  $handler->display->display_options['tab_options']['weight'] = '0';
  $translatables['mcapi_exchanges_user'] = array(
    t('Defaults'),
    t('Exchange list'),
    t('more'),
    t('Apply'),
    t('Reset'),
    t('Sort By'),
    t('Asc'),
    t('Desc'),
    t('Items per page'),
    t('- All -'),
    t('Offset'),
    t('Add a new exchange'),
    t('There are no exchanges in the system yet'),
    t('Timestamp'),
    t('Description'),
    t('Payer'),
    t('Payee'),
    t('Amount'),
    t('incoming'),
    t('outgoing'),
    t('Change'),
    t('Other user'),
    t('All'),
    t('All exchanges'),
    t('Click the column headings to re-sort'),
    t('Exchanges in which this user participated'),
  );
  return $view;
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
class ______________views_handler_field_exchanges extends views_handler_field {
  /**
   * Override init function to provide generic option to link to user.
   */
  function init(&$view, &$data) {
    parent::init($view, $data);
    if (!empty($this->options['link_to_exchange'])) {
      $this->additional_fields['xid'] = 'xid';
    }
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['link_to_exchange'] = array('default' => TRUE);
    return $options;
  }

  /**
   * Provide link to exchange option
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['link_to_exchange'] = array(
      '#title' => t('Link this field to its exchange'),
      '#description' => t('This will override any other link you have set.'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['link_to_exchange'],
    );
  }

  function render_link($data, $values) {
    if (!empty($this->options['link_to_exchange']) && user_access('access user profiles') && $values->{$this->aliases['xid']} && $data !== NULL && $data !== '') {
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = "exchange/" . $values->{$this->aliases['xid']};
    }
    return $data;
  }

  function render($values) {
    return $this->render_link(check_plain($values->{$this->field_alias}), $values);
  }
}
