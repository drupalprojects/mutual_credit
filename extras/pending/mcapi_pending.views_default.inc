<?php

function mcapi_pending_views_default_views() {
  $view = new view;
  $view->name = 'signatures';
  $view->description = '';
  $view->tag = 'default';
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
  $handler->display->display_options['fields']['worth_1']['element_label_colon'] = 1;
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
  $handler->display->display_options['fields']['sign']['id'] = 'sign';
  $handler->display->display_options['fields']['sign']['table'] = 'mcapi_signatures';
  $handler->display->display_options['fields']['sign']['field'] = 'sign';
  $handler->display->display_options['fields']['sign']['label'] = '';
  $handler->display->display_options['fields']['sign']['element_label_colon'] = FALSE;
  $handler->display->display_options['fields']['sign']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['sign']['hide_empty'] = 0;
  $handler->display->display_options['fields']['sign']['empty_zero'] = 0;
  $handler->display->display_options['fields']['sign']['hide_alter_empty'] = 0;
  $handler->display->display_options['fields']['sign']['text'] = 'sign this';
  /* Contextual filter: Signatures: Signatory */
  $handler->display->display_options['arguments']['uid']['id'] = 'uid';
  $handler->display->display_options['arguments']['uid']['table'] = 'mcapi_signatures';
  $handler->display->display_options['arguments']['uid']['field'] = 'uid';
  $handler->display->display_options['arguments']['uid']['default_action'] = 'default';
  $handler->display->display_options['arguments']['uid']['default_argument_type'] = 'user';
  $handler->display->display_options['arguments']['uid']['default_argument_options']['user'] = FALSE;
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
  $handler->display->display_options['display_description'] = 'current user pending signature';
  $handler->display->display_options['defaults']['header'] = FALSE;
  $handler->display->display_options['block_description'] = 'My pending';

  /* Display: user pending */
  $handler = $view->new_display('block', 'user pending', 'user_pending');
  $handler->display->display_options['display_description'] = 'Signature needed';
  $handler->display->display_options['block_description'] = 'User pending';


  /* Display: i_wait */
  $handler = $view->new_display('block', 'i_wait', 'block_1');
  $handler->display->display_options['display_description'] = 'pending transactions needing other signatures';
  $handler->display->display_options['defaults']['relationships'] = FALSE;
  /* Relationship: Signatures: Signatory */
  $handler->display->display_options['relationships']['uid']['id'] = 'uid';
  $handler->display->display_options['relationships']['uid']['table'] = 'mcapi_signatures';
  $handler->display->display_options['relationships']['uid']['field'] = 'uid';
  $handler->display->display_options['relationships']['uid']['required'] = 1;
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
  $handler->display->display_options['defaults']['arguments'] = FALSE;
  $handler->display->display_options['filter_groups']['groups'] = array(
    1 => 'OR',
    2 => 'AND',
  );
  $handler->display->display_options['defaults']['filters'] = FALSE;
  /* Filter criterion: User: Current */
  $handler->display->display_options['filters']['uid_current']['id'] = 'uid_current';
  $handler->display->display_options['filters']['uid_current']['table'] = 'users';
  $handler->display->display_options['filters']['uid_current']['field'] = 'uid_current';
  $handler->display->display_options['filters']['uid_current']['relationship'] = 'payee';
  $handler->display->display_options['filters']['uid_current']['value'] = '1';
  $handler->display->display_options['filters']['uid_current']['group'] = 1;
  /* Filter criterion: User: Current */
  $handler->display->display_options['filters']['uid_current_1']['id'] = 'uid_current_1';
  $handler->display->display_options['filters']['uid_current_1']['table'] = 'users';
  $handler->display->display_options['filters']['uid_current_1']['field'] = 'uid_current';
  $handler->display->display_options['filters']['uid_current_1']['relationship'] = 'payer';
  $handler->display->display_options['filters']['uid_current_1']['value'] = '1';
  $handler->display->display_options['filters']['uid_current_1']['group'] = 1;
  /* Filter criterion: Signatures: Signatory */
  $handler->display->display_options['filters']['uid']['id'] = 'uid';
  $handler->display->display_options['filters']['uid']['table'] = 'mcapi_signatures';
  $handler->display->display_options['filters']['uid']['field'] = 'uid';
  $handler->display->display_options['filters']['uid']['value'] = '0';
  $handler->display->display_options['filters']['uid']['group'] = 2;
  /* Filter criterion: Transaction: State */
  $handler->display->display_options['filters']['state']['id'] = 'state';
  $handler->display->display_options['filters']['state']['table'] = 'mcapi_transactions';
  $handler->display->display_options['filters']['state']['field'] = 'state';
  $handler->display->display_options['filters']['state']['value'] = array(
    1 => TRANSACTION_STATE_PENDING,
  );
  $handler->display->display_options['filters']['state']['group'] = 2;

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
    t('user pending'),
    t('Pending user signature'),
    t('Signature needed'),
    t('User pending'),
  );

  $views[$view->name] = $view;


  return $views;
}