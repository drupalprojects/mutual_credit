<?php

function mcapi_pending_views_default_views() {
  $view = new view;
  $view->name = 'pending_test';
  $view->description = '';
  $view->tag = 'default';
  $view->base_table = 'mcapi_transactions';
  $view->human_name = 'pending test';
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
  /* Field: Transaction: Value */
  $handler->display->display_options['fields']['worth']['id'] = 'worth';
  $handler->display->display_options['fields']['worth']['table'] = 'field_data_worth';
  $handler->display->display_options['fields']['worth']['field'] = 'worth';
  $handler->display->display_options['fields']['worth']['label'] = '';
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
  $handler->display->display_options['fields']['worth']['element_label_colon'] = FALSE;
  $handler->display->display_options['fields']['worth']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['worth']['hide_empty'] = 0;
  $handler->display->display_options['fields']['worth']['empty_zero'] = 0;
  $handler->display->display_options['fields']['worth']['hide_alter_empty'] = 0;
  $handler->display->display_options['fields']['worth']['click_sort_column'] = 'currcode';
  $handler->display->display_options['fields']['worth']['field_api_classes'] = 0;
  /* Field: Transaction: created */
  $handler->display->display_options['fields']['created']['id'] = 'created';
  $handler->display->display_options['fields']['created']['table'] = 'mcapi_transactions';
  $handler->display->display_options['fields']['created']['field'] = 'created';
  $handler->display->display_options['fields']['created']['label'] = '';
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
  $handler->display->display_options['fields']['sign']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['sign']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['sign']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['sign']['alter']['external'] = 0;
  $handler->display->display_options['fields']['sign']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['sign']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['sign']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['sign']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['sign']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['sign']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['sign']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['sign']['alter']['html'] = 0;
  $handler->display->display_options['fields']['sign']['element_label_colon'] = FALSE;
  $handler->display->display_options['fields']['sign']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['sign']['hide_empty'] = 0;
  $handler->display->display_options['fields']['sign']['empty_zero'] = 0;
  $handler->display->display_options['fields']['sign']['hide_alter_empty'] = 0;
  $handler->display->display_options['fields']['sign']['text'] = 'sign this';
  /* Filter criterion: Signatures: Signatory */
  $handler->display->display_options['filters']['uid']['id'] = 'uid';
  $handler->display->display_options['filters']['uid']['table'] = 'mcapi_signatures';
  $handler->display->display_options['filters']['uid']['field'] = 'uid';
  $handler->display->display_options['filters']['uid']['operator'] = 'in';
  $handler->display->display_options['filters']['uid']['value'] = '1';
  /* Filter criterion: Signatures: Whether the signature has happened */
  $handler->display->display_options['filters']['signed']['id'] = 'signed';
  $handler->display->display_options['filters']['signed']['table'] = 'mcapi_signatures';
  $handler->display->display_options['filters']['signed']['field'] = 'signed';
  $handler->display->display_options['filters']['signed']['value'] = '';
  /* Filter criterion: Transaction: State */
  $handler->display->display_options['filters']['state']['id'] = 'state';
  $handler->display->display_options['filters']['state']['table'] = 'mcapi_transactions';
  $handler->display->display_options['filters']['state']['field'] = 'state';
  $handler->display->display_options['filters']['state']['value'] = array(
    1 => '1',
  );

  /* Display: my pending */
  $handler = $view->new_display('block', 'my pending', 'block_1');
  $handler->display->display_options['title'] = 'My signature needed';
  $handler->display->display_options['display_description'] = 'current user pending signature';
  $handler->display->display_options['defaults']['header'] = FALSE;
  /* Header: Global: Text area */
  $handler->display->display_options['footer']['area']['id'] = 'area';
  $handler->display->display_options['footer']['area']['table'] = 'views';
  $handler->display->display_options['footer']['area']['field'] = 'area';
  $handler->display->display_options['footer']['area']['empty'] = FALSE;
  $handler->display->display_options['footer']['area']['content'] = '<a href = "/transactions/signall">Sign all</a>';
  $handler->display->display_options['footer']['area']['format'] = 'plain_text';
  $handler->display->display_options['footer']['area']['tokenize'] = 0;
  $handler->display->display_options['block_description'] = 'My pending';

  /* Display: user pending */
  $handler = $view->new_display('block', 'user pending', 'block_2');
  $handler->display->display_options['title'] = 'Pending user signature';
  $handler->display->display_options['display_description'] = 'Signature needed';
  $handler->display->display_options['defaults']['arguments'] = FALSE;
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
  $handler->display->display_options['defaults']['filters'] = FALSE;
  /* Filter criterion: Signatures: Whether the signature has happened */
  $handler->display->display_options['filters']['signed']['id'] = 'signed';
  $handler->display->display_options['filters']['signed']['table'] = 'mcapi_signatures';
  $handler->display->display_options['filters']['signed']['field'] = 'signed';
  $handler->display->display_options['filters']['signed']['value'] = '';
  $handler->display->display_options['filters']['signed']['group'] = 0;
  /* Filter criterion: Transaction: State */
  $handler->display->display_options['filters']['state']['id'] = 'state';
  $handler->display->display_options['filters']['state']['table'] = 'mcapi_transactions';
  $handler->display->display_options['filters']['state']['field'] = 'state';
  $handler->display->display_options['filters']['state']['value'] = array(
    1 => '1',
  );
  $handler->display->display_options['filters']['state']['group'] = 0;
  $handler->display->display_options['block_description'] = 'User pending';

  $views[$view->name] = $view;


  return $views;
}