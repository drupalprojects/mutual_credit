<?php

function mcapi_signatures_views_default_views() {
  $view = new view();
  $view->name = 'sig_blocks';
  $view->description = 'pending transactions for which the given user is a signatory';
  $view->tag = 'default';
  $view->base_table = 'mcapi_transactions';
  $view->human_name = 'Signatures waiting';
  $view->core = 7;
  $view->api_version = '3.0';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */
  /* Display: Master */
  $handler = $view->new_display('default', 'Master', 'default');
  $handler->display->display_options['title'] = 'Pending transactions';
  $handler->display->display_options['use_more_always'] = FALSE;
  $handler->display->display_options['use_more_text'] = 'plus';
  $handler->display->display_options['access']['type'] = 'none';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['query']['options']['distinct'] = TRUE;
  $handler->display->display_options['query']['options']['query_comment'] = FALSE;
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['exposed_form']['options']['submit_button'] = 'Appliquer';
  $handler->display->display_options['exposed_form']['options']['reset_button_label'] = 'Remise à zéro';
  $handler->display->display_options['exposed_form']['options']['exposed_sorts_label'] = 'Trier par';
  $handler->display->display_options['pager']['type'] = 'none';
  $handler->display->display_options['style_plugin'] = 'default';
  $handler->display->display_options['row_plugin'] = 'fields';
  $handler->display->display_options['row_options']['inline'] = array(
    'worth' => 'worth',
    'sign' => 'sign',
  );
  /* Field: Transaction: Numéro de série */
  $handler->display->display_options['fields']['serial']['id'] = 'serial';
  $handler->display->display_options['fields']['serial']['table'] = 'mcapi_transactions';
  $handler->display->display_options['fields']['serial']['field'] = 'serial';
  $handler->display->display_options['fields']['serial']['label'] = '';
  $handler->display->display_options['fields']['serial']['alter']['alter_text'] = TRUE;
  $handler->display->display_options['fields']['serial']['alter']['text'] = 'Transaction #[serial]';
  $handler->display->display_options['fields']['serial']['alter']['make_link'] = TRUE;
  $handler->display->display_options['fields']['serial']['alter']['path'] = 'transaction/[serial]';
  $handler->display->display_options['fields']['serial']['element_label_colon'] = FALSE;
  $handler->display->display_options['fields']['serial']['link_to_transaction'] = 0;
  /* Field: Transaction: Worth */
  $handler->display->display_options['fields']['worth']['id'] = 'worth';
  $handler->display->display_options['fields']['worth']['table'] = 'field_data_worth';
  $handler->display->display_options['fields']['worth']['field'] = 'worth';
  $handler->display->display_options['fields']['worth']['label'] = '';
  $handler->display->display_options['fields']['worth']['element_label_colon'] = FALSE;
  $handler->display->display_options['fields']['worth']['click_sort_column'] = 'currcode';
  /* Field: Signatures: Sign here */
  $handler->display->display_options['fields']['sign']['id'] = 'sign';
  $handler->display->display_options['fields']['sign']['table'] = 'mcapi_signatures';
  $handler->display->display_options['fields']['sign']['field'] = 'sign';
  $handler->display->display_options['fields']['sign']['label'] = '';
  $handler->display->display_options['fields']['sign']['element_label_colon'] = FALSE;
  /* Sort criterion: Transaction: Créé */
  $handler->display->display_options['sorts']['created']['id'] = 'created';
  $handler->display->display_options['sorts']['created']['table'] = 'mcapi_transactions';
  $handler->display->display_options['sorts']['created']['field'] = 'created';
  $handler->display->display_options['sorts']['created']['granularity'] = 'minute';
  /* Contextual filter: Signatures: ...is a signatory */
  $handler->display->display_options['arguments']['uid']['id'] = 'uid';
  $handler->display->display_options['arguments']['uid']['table'] = 'mcapi_signatures';
  $handler->display->display_options['arguments']['uid']['field'] = 'uid';
  $handler->display->display_options['arguments']['uid']['default_action'] = 'default';
  $handler->display->display_options['arguments']['uid']['exception']['title'] = 'Tout';
  $handler->display->display_options['arguments']['uid']['default_argument_type'] = 'current_user';
  $handler->display->display_options['arguments']['uid']['summary']['number_of_records'] = '0';
  $handler->display->display_options['arguments']['uid']['summary']['format'] = 'default_summary';
  $handler->display->display_options['arguments']['uid']['summary_options']['items_per_page'] = '25';
  /* Filter criterion: Signatures: Is pending */
  $handler->display->display_options['filters']['pending']['id'] = 'pending';
  $handler->display->display_options['filters']['pending']['table'] = 'mcapi_signatures';
  $handler->display->display_options['filters']['pending']['field'] = 'pending';
  $handler->display->display_options['filters']['pending']['value'] = '1';

  /* Display: my_signature_needed */
  $handler = $view->new_display('block', 'My signature needed', 'my_sig_needed');
  $handler->display->display_options['defaults']['title'] = FALSE;
  $handler->display->display_options['title'] = 'Awaiting my signature...';
  $handler->display->display_options['display_description'] = 'logged in user must sign here';
  $handler->display->display_options['defaults']['hide_admin_links'] = FALSE;
  $handler->display->display_options['block_description'] = 'Awaiting my signature...';

  /* Display: anybody_signatures_needed */
  $handler = $view->new_display('block', "Anybody's signature needed", 'anybodys_sig_needed');
  $handler->display->display_options['display_description'] = 'signatures waiting for given user, or all signatures if no uid passed';
  $handler->display->display_options['defaults']['hide_admin_links'] = FALSE;
  $handler->display->display_options['defaults']['arguments'] = FALSE;
  /* Contextual filter: Signatures: ...is a signatory */
  $handler->display->display_options['arguments']['uid']['id'] = 'uid';
  $handler->display->display_options['arguments']['uid']['table'] = 'mcapi_signatures';
  $handler->display->display_options['arguments']['uid']['field'] = 'uid';
  $handler->display->display_options['arguments']['uid']['default_action'] = 'default';
  $handler->display->display_options['arguments']['uid']['exception']['title'] = 'Tout';
  $handler->display->display_options['arguments']['uid']['default_argument_type'] = 'user';
  $handler->display->display_options['arguments']['uid']['default_argument_options']['user'] = FALSE;
  $handler->display->display_options['arguments']['uid']['summary']['number_of_records'] = '0';
  $handler->display->display_options['arguments']['uid']['summary']['format'] = 'default_summary';
  $handler->display->display_options['arguments']['uid']['summary_options']['items_per_page'] = '25';
  $handler->display->display_options['defaults']['filter_groups'] = FALSE;
  $handler->display->display_options['defaults']['filters'] = FALSE;
  /* Filter criterion: Transaction: État */
  $handler->display->display_options['filters']['state']['id'] = 'state';
  $handler->display->display_options['filters']['state']['table'] = 'mcapi_transactions';
  $handler->display->display_options['filters']['state']['field'] = 'state';
  $handler->display->display_options['filters']['state']['value'] = array(
    -1 => -1,
  );
  $handler->display->display_options['block_description'] = 'Other pending transactions';

  $translatables['sig_blocks'] = array(
    t('Signatory'),
    t('Transaction #[serial]'),
    t('Sign'),
    t('Pending transactions'),
    t('Awaiting my signature...'),
    t('logged in user must sign here'),
    t('My pending transactions'),
    t('signatures waiting for given user, or all signatures if no uid passed'),
    t('Other pending transactions'),
    t('I\'m waiting for signatures'),
    t('Waiting for...'),
    t('Pending transactions which I have already signed'),
    t('My signature needed'),
    t("Anybody's signature needed"),
  );
  $views[$view->name] = $view;

  $view = new view;
  $view->name = 'signatures';
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
  $handler->display->display_options['row_options']['inline'] = array(
    'worth' => 'worth',
    'sign' => 'sign',
  );
  /* Field: Transaction: Serial number */
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
    TRANSACTION_STATE_PENDING => TRANSACTION_STATE_PENDING,
  );
  /* Filter criterion: Signatures: Signatory */
  $handler->display->display_options['filters']['uid']['id'] = 'uid';
  $handler->display->display_options['filters']['uid']['table'] = 'mcapi_signatures';
  $handler->display->display_options['filters']['uid']['field'] = 'uid';
  $handler->display->display_options['filters']['uid']['value'] = '1';

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