<?php

function mcapi_pending_views_default_views() {
  $view = new view;
  $view->name = 'signatures';
  $view->description = 'Signatures awaiting';
  $view->tag = 'mcapi';
  $view->base_table = 'mcapi_transactions';
  $view->human_name = 'signatures needed';
  $view->core = 7;
  $view->api_version = '3.0';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Master */
  $handler = $view->new_display('default', 'Master', 'default');
  $handler->display->display_options['access']['type'] = 'perm';
  $handler->display->display_options['access']['perm'] = 'transact';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['query']['options']['query_comment'] = FALSE;
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'full';
  $handler->display->display_options['pager']['options']['items_per_page'] = '10';
  $handler->display->display_options['pager']['options']['offset'] = '0';
  $handler->display->display_options['pager']['options']['id'] = '0';
  $handler->display->display_options['pager']['options']['expose']['items_per_page_options_all'] = 0;
  $handler->display->display_options['style_plugin'] = 'default';
  $handler->display->display_options['row_plugin'] = 'fields';
  /* Header: Global: Text area */
  $handler->display->display_options['header']['area']['id'] = 'area';
  $handler->display->display_options['header']['area']['table'] = 'views';
  $handler->display->display_options['header']['area']['field'] = 'area';
  $handler->display->display_options['header']['area']['empty'] = FALSE;
  /* Relationship: Signatures: Signatory */
  $handler->display->display_options['relationships']['uid']['id'] = 'uid';
  $handler->display->display_options['relationships']['uid']['table'] = 'mcapi_signatures';
  $handler->display->display_options['relationships']['uid']['field'] = 'uid';
  $handler->display->display_options['relationships']['uid']['required'] = 1;
  /* Field: Transaction: Worth */
  $handler->display->display_options['fields']['worth_1']['id'] = 'worth_1';
  $handler->display->display_options['fields']['worth_1']['table'] = 'field_data_worth';
  $handler->display->display_options['fields']['worth_1']['field'] = 'worth';
  $handler->display->display_options['fields']['worth_1']['label'] = '';
  $handler->display->display_options['fields']['worth_1']['alter']['alter_text'] = 1;
  $handler->display->display_options['fields']['worth_1']['alter']['text'] = 'For [worth_1]';
  $handler->display->display_options['fields']['worth_1']['element_label_colon'] = FALSE;
  $handler->display->display_options['fields']['worth_1']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['worth_1']['hide_empty'] = 0;
  $handler->display->display_options['fields']['worth_1']['empty_zero'] = 0;
  $handler->display->display_options['fields']['worth_1']['hide_alter_empty'] = 1;
  $handler->display->display_options['fields']['worth_1']['click_sort_column'] = 'currcode';
  $handler->display->display_options['fields']['worth_1']['type'] = 'worth_field';
  $handler->display->display_options['fields']['worth_1']['field_api_classes'] = 0;
  /* Field: Transaction: created */
  $handler->display->display_options['fields']['created']['id'] = 'created';
  $handler->display->display_options['fields']['created']['table'] = 'mcapi_transactions';
  $handler->display->display_options['fields']['created']['field'] = 'created';
  $handler->display->display_options['fields']['created']['label'] = '';
  $handler->display->display_options['fields']['created']['element_label_colon'] = FALSE;
  $handler->display->display_options['fields']['created']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['created']['hide_empty'] = 0;
  $handler->display->display_options['fields']['created']['empty_zero'] = 0;
  $handler->display->display_options['fields']['created']['hide_alter_empty'] = 0;
  $handler->display->display_options['fields']['created']['date_format'] = 'time ago';
  /* Field: Signatures: Sign here link */
  $handler->display->display_options['fields']['sign_1']['id'] = 'sign_1';
  $handler->display->display_options['fields']['sign_1']['table'] = 'mcapi_signatures';
  $handler->display->display_options['fields']['sign_1']['field'] = 'sign';
  $handler->display->display_options['fields']['sign_1']['label'] = '';
  $handler->display->display_options['fields']['sign_1']['element_label_colon'] = FALSE;
  $handler->display->display_options['fields']['sign_1']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['sign_1']['hide_empty'] = 1;
  $handler->display->display_options['fields']['sign_1']['empty_zero'] = 0;
  $handler->display->display_options['fields']['sign_1']['hide_alter_empty'] = 0;
  /* Contextual filter: Signatures: Signatory */
  $handler->display->display_options['arguments']['uid']['id'] = 'uid';
  $handler->display->display_options['arguments']['uid']['table'] = 'mcapi_signatures';
  $handler->display->display_options['arguments']['uid']['field'] = 'uid';
  $handler->display->display_options['arguments']['uid']['default_action'] = 'default';
  $handler->display->display_options['arguments']['uid']['default_argument_type'] = 'current_user';
  $handler->display->display_options['arguments']['uid']['default_argument_skip_url'] = 0;
  $handler->display->display_options['arguments']['uid']['summary']['number_of_records'] = '0';
  $handler->display->display_options['arguments']['uid']['summary']['format'] = 'default_summary';
  $handler->display->display_options['arguments']['uid']['summary_options']['items_per_page'] = '25';
  $handler->display->display_options['arguments']['uid']['break_phrase'] = 0;
  $handler->display->display_options['arguments']['uid']['not'] = 0;
  /* Filter criterion: Signatures: Is pending */
  $handler->display->display_options['filters']['pending']['id'] = 'pending';
  $handler->display->display_options['filters']['pending']['table'] = 'mcapi_signatures';
  $handler->display->display_options['filters']['pending']['field'] = 'pending';
  $handler->display->display_options['filters']['pending']['value'] = '1';
  $handler->display->display_options['filters']['pending']['group'] = 1;
  /* Filter criterion: Transaction: State */
  $handler->display->display_options['filters']['state']['id'] = 'state';
  $handler->display->display_options['filters']['state']['table'] = 'mcapi_transactions';
  $handler->display->display_options['filters']['state']['field'] = 'state';
  $handler->display->display_options['filters']['state']['value'] = array(
    1 => TRANSACTION_STATE_PENDING,
  );
  $handler->display->display_options['filters']['state']['group'] = 1;

  /* Display: my pending */
  $handler = $view->new_display('block', 'my pending', 'my_pending');
  $handler->display->display_options['display_description'] = 'pending signatures for current user';
  $handler->display->display_options['defaults']['header'] = FALSE;
  $handler->display->display_options['block_description'] = 'My signatures pending';

  /* Display: user pending */
  $handler = $view->new_display('block', 'user pending', 'user_pending');
  $handler->display->display_options['display_description'] = 'Signature needed';
  $handler->display->display_options['block_description'] = 'User signatures pending';

  $translatables['signatures'] = array(
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
    t('Signatory'),
    t('Worth'),
    t('sign this'),
    t('All'),
    t('my pending'),
    t('My signature needed'),
    t('current user pending signature'),
    t('<a href = "/transactions/signall">Sign all</a>'),
    t('My pending'),
    t('User pending'),
    t('Pending user signature'),
    t('Signature needed'),
    t('User pending'),
  );

  $views[$view->name] = $view;

  $view = new view;
  $view->name = 'pending_transactions';
  $view->description = 'pending transactions for which the given user is a signatory';
  $view->tag = 'default';
  $view->base_table = 'mcapi_transactions';
  $view->human_name = 'Pending transactions';
  $view->core = 7;
  $view->api_version = '3.0';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Master */
  $handler = $view->new_display('default', 'Master', 'default');
  $handler->display->display_options['title'] = 'Pending transactions';
  $handler->display->display_options['access']['type'] = 'none';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['query']['options']['distinct'] = TRUE;
  $handler->display->display_options['query']['options']['query_comment'] = FALSE;
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'none';
  $handler->display->display_options['style_plugin'] = 'default';
  $handler->display->display_options['row_plugin'] = 'fields';
  /* Field: Transaction: Unique serial number */
  $handler->display->display_options['fields']['serial']['id'] = 'serial';
  $handler->display->display_options['fields']['serial']['table'] = 'mcapi_transactions';
  $handler->display->display_options['fields']['serial']['field'] = 'serial';
  $handler->display->display_options['fields']['serial']['label'] = '';
  $handler->display->display_options['fields']['serial']['alter']['alter_text'] = 1;
  $handler->display->display_options['fields']['serial']['alter']['text'] = 'Transaction #[serial]';
  $handler->display->display_options['fields']['serial']['alter']['make_link'] = 1;
  $handler->display->display_options['fields']['serial']['alter']['path'] = 'transaction/[serial]';
  $handler->display->display_options['fields']['serial']['element_label_colon'] = FALSE;
  $handler->display->display_options['fields']['serial']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['serial']['hide_empty'] = 0;
  $handler->display->display_options['fields']['serial']['empty_zero'] = 0;
  $handler->display->display_options['fields']['serial']['hide_alter_empty'] = 1;
  $handler->display->display_options['fields']['serial']['link_to_transaction'] = 0;
  /* Field: Transaction: Worth */
  $handler->display->display_options['fields']['worth']['id'] = 'worth';
  $handler->display->display_options['fields']['worth']['table'] = 'field_data_worth';
  $handler->display->display_options['fields']['worth']['field'] = 'worth';
  $handler->display->display_options['fields']['worth']['label'] = '';
  $handler->display->display_options['fields']['worth']['element_label_colon'] = FALSE;
  $handler->display->display_options['fields']['worth']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['worth']['hide_empty'] = 0;
  $handler->display->display_options['fields']['worth']['empty_zero'] = 0;
  $handler->display->display_options['fields']['worth']['hide_alter_empty'] = 1;
  $handler->display->display_options['fields']['worth']['click_sort_column'] = 'currcode';
  $handler->display->display_options['fields']['worth']['type'] = 'worth_field';
  $handler->display->display_options['fields']['worth']['field_api_classes'] = 0;
  /* Field: Signatures: Sign */
  $handler->display->display_options['fields']['sign']['id'] = 'sign';
  $handler->display->display_options['fields']['sign']['table'] = 'mcapi_signatures';
  $handler->display->display_options['fields']['sign']['field'] = 'sign';
  $handler->display->display_options['fields']['sign']['label'] = '';
  $handler->display->display_options['fields']['sign']['element_label_colon'] = FALSE;
  $handler->display->display_options['fields']['sign']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['sign']['hide_empty'] = 0;
  $handler->display->display_options['fields']['sign']['empty_zero'] = 0;
  $handler->display->display_options['fields']['sign']['hide_alter_empty'] = 1;
  $handler->display->display_options['fields']['sign']['link_title'] = 'Sign';
  /* Contextual filter: Signatures: Signatory */
  $handler->display->display_options['arguments']['uid']['id'] = 'uid';
  $handler->display->display_options['arguments']['uid']['table'] = 'mcapi_signatures';
  $handler->display->display_options['arguments']['uid']['field'] = 'uid';
  $handler->display->display_options['arguments']['uid']['default_argument_type'] = 'fixed';
  $handler->display->display_options['arguments']['uid']['default_argument_skip_url'] = 0;
  $handler->display->display_options['arguments']['uid']['summary']['number_of_records'] = '0';
  $handler->display->display_options['arguments']['uid']['summary']['format'] = 'default_summary';
  $handler->display->display_options['arguments']['uid']['summary_options']['items_per_page'] = '25';
  $handler->display->display_options['arguments']['uid']['break_phrase'] = 0;
  $handler->display->display_options['arguments']['uid']['not'] = 0;
  /* Filter criterion: Transaction: State */
  $handler->display->display_options['filters']['state']['id'] = 'state';
  $handler->display->display_options['filters']['state']['table'] = 'mcapi_transactions';
  $handler->display->display_options['filters']['state']['field'] = 'state';
  $handler->display->display_options['filters']['state']['value'] = array(
    -1 => '-1',
  );

  /* Display: Block */
  $handler = $view->new_display('block', 'Block', 'block');
  $handler->display->display_options['block_description'] = 'Pending transactions';
  $translatables['pending_transactions'] = array(
    t('Transaction #[serial]'),
    t('Pending transactions'),
  );

  $views[$view->name] = $view;
  return $views;
}