<?php
//Id;

/*
 * implemements views hook_views_data_alter
 */
function mcapi_taxonomy_views_default_views() {
  $view = new view;
  $view->name = 'mcapi_transaction_categories';
  $view->description = 'number of transactions in all categories of a given vocab';
  $view->tag = 'mcapi';
  $view->base_table = 'mcapi_transactions';
  $view->human_name = 'transactions in categories';
  $view->core = 7;
  $view->api_version = '3.0';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Master */
  $handler = $view->new_display('default', 'Master', 'default');
  $handler->display->display_options['defaults']['relationships'] = FALSE;
  /* Relationship: Transaction: Taxonomy terms on transaction */
  $handler->display->display_options['relationships']['term_transaction_tid']['id'] = 'term_transaction_tid';
  $handler->display->display_options['relationships']['term_transaction_tid']['table'] = 'mcapi_transactions';
  $handler->display->display_options['relationships']['term_transaction_tid']['field'] = 'term_transaction_tid';
  $handler->display->display_options['relationships']['term_transaction_tid']['required'] = 1;
  $handler->display->display_options['relationships']['term_transaction_tid']['vocabularies'] = array(
    'tags' => 'tags',
  );
  $handler->display->display_options['defaults']['fields'] = FALSE;
  /* Field: Taxonomy term: Name */
  $handler->display->display_options['fields']['name']['id'] = 'name';
  $handler->display->display_options['fields']['name']['table'] = 'taxonomy_term_data';
  $handler->display->display_options['fields']['name']['field'] = 'name';
  $handler->display->display_options['fields']['name']['relationship'] = 'term_transaction_tid';
  $handler->display->display_options['fields']['name']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['name']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['name']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['name']['alter']['external'] = 0;
  $handler->display->display_options['fields']['name']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['name']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['name']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['name']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['name']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['name']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['name']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['name']['alter']['html'] = 0;
  $handler->display->display_options['fields']['name']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['name']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['name']['hide_empty'] = 0;
  $handler->display->display_options['fields']['name']['empty_zero'] = 0;
  $handler->display->display_options['fields']['name']['link_to_taxonomy'] = 1;
  /* Field: Transaction: Transaction ID */
  $handler->display->display_options['fields']['xid']['id'] = 'xid';
  $handler->display->display_options['fields']['xid']['table'] = 'mcapi_transactions';
  $handler->display->display_options['fields']['xid']['field'] = 'xid';
  $handler->display->display_options['fields']['xid']['group_type'] = 'count';
  $handler->display->display_options['fields']['xid']['label'] = 'Count';
  $handler->display->display_options['fields']['xid']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['xid']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['xid']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['xid']['alter']['external'] = 0;
  $handler->display->display_options['fields']['xid']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['xid']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['xid']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['xid']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['xid']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['xid']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['xid']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['xid']['alter']['html'] = 0;
  $handler->display->display_options['fields']['xid']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['xid']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['xid']['hide_empty'] = 0;
  $handler->display->display_options['fields']['xid']['empty_zero'] = 0;
  $handler->display->display_options['defaults']['arguments'] = FALSE;
  /* Contextual filter: Transaction: Involved user */
  $handler->display->display_options['arguments']['participant']['id'] = 'participant';
  $handler->display->display_options['arguments']['participant']['table'] = 'mcapi_transactions';
  $handler->display->display_options['arguments']['participant']['field'] = 'participant';
  $handler->display->display_options['arguments']['participant']['default_argument_type'] = 'fixed';
  $handler->display->display_options['arguments']['participant']['summary']['format'] = 'default_summary';
  $handler->display->display_options['defaults']['filters'] = FALSE;
  /* Filter criterion: Transaction: Recorded */
  $handler->display->display_options['filters']['created']['id'] = 'created';
  $handler->display->display_options['filters']['created']['table'] = 'mcapi_transactions';
  $handler->display->display_options['filters']['created']['field'] = 'created';
  $handler->display->display_options['filters']['created']['operator'] = '>';
  $handler->display->display_options['filters']['created']['value']['type'] = 'offset';
  $handler->display->display_options['filters']['created']['exposed'] = TRUE;
  $handler->display->display_options['filters']['created']['expose']['operator_id'] = 'created_op';
  $handler->display->display_options['filters']['created']['expose']['label'] = 'Since';
  $handler->display->display_options['filters']['created']['expose']['operator'] = 'created_op';
  $handler->display->display_options['filters']['created']['expose']['identifier'] = 'created';
  $handler->display->display_options['filters']['created']['expose']['remember'] = 1;
  $handler->display->display_options['filters']['created']['expose']['multiple'] = FALSE;
  /* Filter criterion: Transaction: Recorded */
  $handler->display->display_options['filters']['created_1']['id'] = 'created_1';
  $handler->display->display_options['filters']['created_1']['table'] = 'mcapi_transactions';
  $handler->display->display_options['filters']['created_1']['field'] = 'created';
  $handler->display->display_options['filters']['created_1']['operator'] = '<=';
  $handler->display->display_options['filters']['created_1']['value']['type'] = 'offset';
  $handler->display->display_options['filters']['created_1']['exposed'] = TRUE;
  $handler->display->display_options['filters']['created_1']['expose']['operator_id'] = 'created_1_op';
  $handler->display->display_options['filters']['created_1']['expose']['label'] = 'Until';
  $handler->display->display_options['filters']['created_1']['expose']['operator'] = 'created_1_op';
  $handler->display->display_options['filters']['created_1']['expose']['identifier'] = 'created_1';
  $handler->display->display_options['filters']['created_1']['expose']['remember'] = 1;
  $handler->display->display_options['filters']['created_1']['expose']['multiple'] = FALSE;
  /* Filter criterion: Transaction: Currency ID */
  $handler->display->display_options['filters']['cid']['id'] = 'cid';
  $handler->display->display_options['filters']['cid']['table'] = 'mcapi_transactions';
  $handler->display->display_options['filters']['cid']['field'] = 'cid';
  $handler->display->display_options['filters']['cid']['exposed'] = TRUE;
  $handler->display->display_options['filters']['cid']['expose']['operator_id'] = 'cid_op';
  $handler->display->display_options['filters']['cid']['expose']['label'] = 'Currency ID';
  $handler->display->display_options['filters']['cid']['expose']['operator'] = 'cid_op';
  $handler->display->display_options['filters']['cid']['expose']['identifier'] = 'cid';
  $handler->display->display_options['filters']['cid']['expose']['reduce'] = 0;
  /* Filter criterion: Transaction: Involved user (must be exposed) */
  $handler->display->display_options['filters']['participant']['id'] = 'participant';
  $handler->display->display_options['filters']['participant']['table'] = 'mcapi_transactions';
  $handler->display->display_options['filters']['participant']['field'] = 'participant';
  $handler->display->display_options['filters']['participant']['operator'] = '=';
  $handler->display->display_options['filters']['participant']['value'] = '';
  $handler->display->display_options['filters']['participant']['exposed'] = TRUE;
  $handler->display->display_options['filters']['participant']['expose']['operator_id'] = '';
  $handler->display->display_options['filters']['participant']['expose']['label'] = 'Involved user (must be exposed)';
  $handler->display->display_options['filters']['participant']['expose']['operator'] = 'participant_op';
  $handler->display->display_options['filters']['participant']['expose']['identifier'] = 'participant';
  $handler->display->display_options['filters']['participant']['expose']['reduce'] = 0;
  $translatables['test_taxonomy'] = array(
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
    t('term'),
    t('Name'),
    t('transactions in categories'),
    t('Count'),
    t('All'),
    t('Since'),
    t('Until'),
    t('Currency ID'),
    t('Involved user (must be exposed)'),
  );
  $views[$view->name] = $view;
  return $views;
}


/*
 * implemements views hook_views_data_alter
 * injects taxonomy handlers into existing views
 */
/*
 * implemements views hook_views_data_alter
 * injects taxonomy handlers into existing views
 */
function mcapi_taxonomy_views_default_views_alter(&$items) {
  $handler = $items['user_trades']->display['default']->handler;
  /* Relationship: Transaction index: Taxonomy terms on transaction */
  $handler->display->display_options['relationships']['term_transaction_tid']['id'] = 'term_transaction_tid';
  $handler->display->display_options['relationships']['term_transaction_tid']['table'] = 'mcapi_index';
  $handler->display->display_options['relationships']['term_transaction_tid']['field'] = 'term_transaction_tid';
  $handler->display->display_options['relationships']['term_transaction_tid']['required'] = 0;
  $handler->display->display_options['relationships']['term_transaction_tid']['vocabularies'] = array(
    'tags' => 'tags',
  );
  /* Filter criterion: Taxonomy term: Name */
  $handler->display->display_options['filters']['name']['id'] = 'name';
  $handler->display->display_options['filters']['name']['table'] = 'taxonomy_term_data';
  $handler->display->display_options['filters']['name']['field'] = 'name';
  $handler->display->display_options['filters']['name']['relationship'] = 'term_transaction_tid';
  $handler->display->display_options['filters']['name']['exposed'] = TRUE;
  $handler->display->display_options['filters']['name']['expose']['operator_id'] = 'name_op';
  $handler->display->display_options['filters']['name']['expose']['label'] = 'Category';
  $handler->display->display_options['filters']['name']['expose']['operator'] = 'name_op';
  $handler->display->display_options['filters']['name']['expose']['identifier'] = 'name';
  $handler->display->display_options['filters']['name']['expose']['required'] = FALSE;
  $handler->display->display_options['filters']['name']['expose']['multiple'] = FALSE;

  $handler = $items['transactions_summary']->display['default']->handler;
  /* Relationship: Transaction: Taxonomy terms on transaction */
  $handler->display->display_options['relationships']['term_transaction_tid_1']['id'] = 'term_transaction_tid_1';
  $handler->display->display_options['relationships']['term_transaction_tid_1']['table'] = 'mcapi_transactions';
  $handler->display->display_options['relationships']['term_transaction_tid_1']['field'] = 'term_transaction_tid';
  $handler->display->display_options['relationships']['term_transaction_tid_1']['required'] = 0;
  $handler->display->display_options['relationships']['term_transaction_tid_1']['vocabularies'] = array(
    'tags' => 'tags',
  );
  /* Filter criterion: Taxonomy term: Name */
  $handler->display->display_options['filters']['name']['id'] = 'name';
  $handler->display->display_options['filters']['name']['table'] = 'taxonomy_term_data';
  $handler->display->display_options['filters']['name']['field'] = 'name';
  $handler->display->display_options['filters']['name']['relationship'] = 'term_transaction_tid_1';
  $handler->display->display_options['filters']['name']['exposed'] = TRUE;
  $handler->display->display_options['filters']['name']['expose']['operator_id'] = 'name_op';
  $handler->display->display_options['filters']['name']['expose']['label'] = 'Category';
  $handler->display->display_options['filters']['name']['expose']['operator'] = 'name_op';
  $handler->display->display_options['filters']['name']['expose']['identifier'] = 'name';
  $handler->display->display_options['filters']['name']['expose']['required'] = FALSE;
  $handler->display->display_options['filters']['name']['expose']['multiple'] = FALSE;



}