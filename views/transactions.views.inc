<?php
//$Id$
/**
 * @file
 * Contains default views on behalf of the transactions  module.
 */


function transactions_views_handlers() {
  $handlers= array(
    'info' => array(
      'path' => drupal_get_path('module', 'transactions') .'/views',
    ),
    'handlers' => array(
      'views_handler_field_transactions_payer_uid' => array(
        'parent' => 'views_handler_field'
      ),
      'views_handler_field_transactions_payee_uid' => array(
        'parent' => 'views_handler_field'
      ),
      'views_handler_filter_transaction_types' => array(
        'parent' => 'views_handler_filter_many_to_one'
      )
    ),
  );
  return $handlers;
}
//this was derived from the similar funcntionality in cck, where a dropdown is populated by a view
//however is doesn't work
function transcations_views_plugins() {
  return array(
    'module' => 'transactions', // This just tells our themes are elsewhere.
    'display' => array(
      'transaction_form_options' => array(
        'path' => drupal_get_path('module', 'transactions') . '/views',
        // Those strings are not translated for now.
        // We'll need to change that if / when we remove 'no ui'
        'title' => 'Array', // TODO: better name ? (currently not displayed anyway)
        'help' => 'Destination-agnostic display. Mostly useful for programmatic views.',
        'handler' => 'transaction_form_options',
        'no ui' => TRUE, // Programmatic use only.
        'uses hook menu' => FALSE,
        'use ajax' => FALSE,
        'use pager' => FALSE,
        'accept attachments' => FALSE,
      ),
    )
  );

}

function transactions_views_data() {
  $data = array();
  $data['cc_transactions']['table']['group']  = t('Transactions');
  $data['cc_transactions']['table']['base'] = array(
    'field' => 'nid',
    'title' => t('Transaction'),
    'help' => t('Money flows between users.'),
  );
  $data['cc_transactions']['table']['join'] = array(
    'node' => array(
      'left_field' => 'nid',
      'field' => 'nid',
    ),
  );

  $data['cc_transactions']['payer_uid'] = array(
    'title' => t('Payer'), // The item it appears as on the UI,
    'field' => array(
      'help' => t('The user id who spent currency in the transaction.'),
      'handler' => 'views_handler_field_transactions_payer_uid'
    ),
    'filter' => array(
      'help' => t('Show only transactions where the current user paid'),
      'handler' => 'views_handler_filter_user_current'
    ),
    'argument' => array(
      'help' => t('The user who gave currency in the transaction.'),
      'handler' => 'views_handler_argument'
    ),
    'relationship' => array(
      'help' => t('The user who spent currency in the transaction.'),
      'base' => 'users',
      'field' => 'payer_uid',
      'handler' => 'views_handler_relationship',
      'label' => t('Payer')
    ),
  );
  $data['cc_transactions']['payee_uid'] = array(
    'title' => t('Payee'),
    'field' => array(
      'help' => t('The user id who received currency in the transaction.'),
      'handler' => 'views_handler_field_transactions_payee_uid',
    ),
    'filter' => array(
      'help' => t('Show only transactions where the current user was paid'),
      'handler' => 'views_handler_filter_user_current',
      'label' => t('User is payee'),
    ),
    'argument' => array(
      'help' => t('The user who received currency in the transaction.'),
      'handler' => 'views_handler_argument'
    ),
    'relationship' => array(
      'help' => t('The user who received currency in the transaction.'),
      'base' => 'users',
      'field' => 'payee_uid',
      'handler' => 'views_handler_relationship',
      'label' => t('Payee'),
    ),
  );
  $data['cc_transactions']['starter_uid'] = array(
    'title' => t('Starter'),
    'field' => array(
      'help' => t('The user id who initiated the transaction.'),
      'handler' => 'views_handler_field',
    ),
    'filter' => array(
      'help' => t('Show only transactions started or not started by a given account'),
      'handler' => 'views_handler_filter_equality',
    ),
    'argument' => array(
      'help' => t('Show only transactions started by a given account'),
      'handler' => 'views_handler_argument',
    ),
    'relationship' => array(
      'help' => t('The account id who initiated the transaction.'),
      'base' => 'users',
      'field' => 'starter_uid',
      'handler' => 'views_handler_relationship',
      'label' => t('starter'),
    ),
  );
  $data['cc_transactions']['completer_uid'] = array(
    'title' => t('Completer'),
    'field' => array(
      'help' => t('The account id who completed the transaction.'),
      'handler' => 'views_handler_field',
    ),
    'filter' => array(
      'help' => t('Show only transactions completed or not completed by a given account'),
      'handler' => 'views_handler_filter_equality',
    ),
    'argument' => array(
      'help' => t('Show only transactions completed by a given account'),
      'handler' => 'views_handler_argument',
    ),
    'relationship' => array(
      'help' => t('The user id who initiated the transaction.'),
      'base' => 'users',
      'field' => 'completer_uid',
      'handler' => 'views_handler_relationship',
      'label' => t('completer'),
    ),
  );
  $data['cc_transactions']['quality'] = array(
    'title' => t('Rating'), 
    'field' => array(
      'help' => t('Quality of the transaction according to the payer'),
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
  );
  $data['cc_transactions']['quantity'] = array(
    'title' => t('Amount'), // The item it appears as on the UI,
    'field' => array(
      'help' => t('Number of units of currency transferred'),
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'help' => t('Transactions above or below a certain value'),
      'handler' => 'views_handler_filter_numeric'
    ),
    'sort' => array(
      'help' => t('The value of the transaction'),
    ),
  );
  $data['cc_transactions']['state'] = array(
    'title' => t('Transaction state'), 
    'field' => array(
      'help' => t('Whether the transaction is completed or pending'),
      //TODO need a new handler which understands the transaction state constants
      'handler' => 'views_handler_field_boolean',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'help' => t('Whether the transaction is completed or pending'),
      //TODO need a new handler which understands the transaction state constants
      'handler' => 'views_handler_filter_boolean_operator',
    ),
  );

  $data['cc_transactions']['transaction_type'] = array(
    'title' => t('Transaction type'),
    'field' => array(
      'help' => t('Which form or module which created the transaction'),
      'handler' => 'views_handler_field',
    ),
    'filter' => array(
      'help' => t('Which form or module which created the transaction'),
      'handler' => 'views_handler_filter_transaction_types',
    ),
  );
  
  $data['cc_balance_cache']['table']['group'] = 'Balances';
  $data['cc_balance_cache']['table']['base'] = array(
    'field' => 'uid',
    'title' => t('User Balance'),
    'help' => t('Balances of users.'),
  );
  $data['cc_balance_cache']['table']['join'] = array(
    'users' => array(
      'left_field' => 'uid',
      'field' => 'uid',
    ),
  );
  $data['cc_balance_cache']['uid'] = array(
    'title' => t('User'),
    'argument' => array(
      'help' => t("The user id"),
      'handler' => 'views_handler_argument',
    )
  );
  $data['cc_balance_cache']['cleared_balance'] = array(
    'title' => t('Balance'),
    'field' => array(
      'help' => t("The user's total cleared income minus total cleared expenditure"),
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'help' => t("The user's total cleared income minus total cleared expenditure"),
      'handler' => 'views_handler_sort',
    )
  );
  $data['cc_balance_cache']['pending_difference'] = array(
    'title' => t('Pending Difference'),
    'field' => array(
      'help' => t("sum of all pending transactions, (@type)", array('@type'=>'')),
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    )
  );
  $data['cc_balance_cache']['pending_balance'] = array(
    'title' => t('Pending Balance'),
    'field' => array(
      'help' => t('balance including pending transactions, (@type)', array('@type'=>'')),
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    )
  );
  $data['cc_balance_cache']['gross_income'] = array(
    'title' => t('Gross Income'),
    'field' => array(
      'help' => t("The user's total completed income ever"),
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    )
  );
  $data['cc_balance_cache']['gross_expenditure'] = array(
    'title' => t('Gross Expenditure'),
    'field' => array(
      'help' => t("The user's total completed expenditure ever"),
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    )
  );
  $data['cc_balance_cache']['quality_mean'] = array(
    'title' => t('Rating'),
    'field' => array(
      'help' => t("The user's average rating for this currency"),
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    )
  );
  return $data;
}


function transactions_views_default_views() {
  $view = cc_transaction_list_view();
  $views[$view->name] = $view;
  
  $view = transactions_pending_view();
  $views[$view->name] = $view;
  return $views;
}

function cc_transaction_list_view() {
  $view = new view;
  $view->name = 'cc_transaction_list';
  $view->description = 'transactions from all users';
  $view->tag = 'transactions';
  $view->view_php = '';
  $view->base_table = 'node';
  $view->is_cacheable = FALSE;
  $view->api_version = 2;
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */
  $handler = $view->new_display('default', 'Defaults', 'default');
  $handler->override_option('relationships', array(
    'payer_uid' => array(
      'label' => t('Payer'),
      'required' => 1,
      'id' => 'payer_uid',
      'table' => 'cc_transactions',
      'field' => 'payer_uid',
      'relationship' => 'none',
    ),
    'payee_uid' => array(
      'id' => 'payee_uid',
      'table' => 'cc_transactions',
      'field' => 'payee_uid',
    ),
  ));
  $cur = currency_load();
  $handler->override_option('fields', array(
    'changed' => array(
      'label' => t('Date'),
      'date_format' => 'custom',
      'custom_date_format' => 'd-m-y',
      'exclude' => 0,
      'id' => 'changed',
      'table' => 'node',
      'field' => 'changed',
      'relationship' => 'none',
    ),
    'name_1' => array(
      'label' => t('Payer'),
      'link_to_user' => 1,
      'exclude' => 0,
      'id' => 'name_1',
      'table' => 'users',
      'field' => 'name',
      'relationship' => 'payer_uid',
    ),
    'name_2' => array(
      'label' => t('Payee'),
      'alter' => array(
        'alter_text' => 0,
        'text' => '',
        'make_link' => 0,
        'path' => '',
        'alt' => '',
        'prefix' => '',
        'suffix' => '',
        'help' => '',
        'trim' => 0,
        'max_length' => '',
        'word_boundary' => 1,
        'ellipsis' => 1,
        'html' => 0,
      ),
      'link_to_user' => 1,
      'overwrite_anonymous' => 0,
      'anonymous_text' => '',
      'exclude' => 0,
      'id' => 'name_2',
      'table' => 'users',
      'field' => 'name',
      'relationship' => 'payee_uid',
    ),
    'title' => array(
      'label' => 'Trade',
      'alter' => array(
        'alter_text' => 0,
        'text' => '',
        'make_link' => 0,
        'path' => '',
        'alt' => '',
        'prefix' => '',
        'suffix' => '',
        'help' => '',
        'trim' => 0,
        'max_length' => '',
        'word_boundary' => 1,
        'ellipsis' => 1,
        'strip_tags' => 0,
        'html' => 0,
      ),
      'link_to_node' => 1,
      'exclude' => 0,
      'id' => 'title',
      'table' => 'node',
      'field' => 'title',
      'relationship' => 'none',
      'override' => array(
        'button' => 'Override',
      ),
    ),
    'quantity' => array(
      'label' => $cur->title,
      'exclude' => 0,
      'id' => 'quantity',
      'table' => 'cc_transactions',
      'field' => 'quantity',
      'relationship' => 'none',
    ),
     'cid' => array(
      'label' => t('Currency ID'),
      'alter' => array(
        'alter_text' => 0,
        'text' => '',
        'make_link' => 0,
        'path' => '',
        'alt' => '',
        'prefix' => '',
        'suffix' => '',
        'help' => '',
        'trim' => 0,
        'max_length' => '',
        'word_boundary' => 1,
        'ellipsis' => 1,
        'html' => 0,
      ),
      'exclude' => 1,
      'id' => 'cid',
      'table' => 'cc_transactions',
      'field' => 'cid',
      'override' => array(
        'button' => 'Override',
      ),
    )
  ));
  
  $handler->override_option('sorts', array(
    'created' => array(
      'order' => 'DESC',
      'granularity' => 'hour',
      'id' => 'created',
      'table' => 'node',
      'field' => 'created',
      'relationship' => 'none',
    ),
  ));
  $handler->override_option('filters', array(
    'type' => array(
      'operator' => 'in',
      'value' => array(
        'transaction' => 'transaction',
      ),
      'group' => '0',
      'exposed' => FALSE,
      'expose' => array(
        'operator' => FALSE,
        'label' => '',
      ),
      'id' => 'type',
      'table' => 'node',
      'field' => 'type',
      'relationship' => 'none',
    ),
    'starter_uid' => array(
      'operator' => '!=',
      'value' => '1',
      'group' => '0',
      'exposed' => FALSE,
      'expose' => array(
        'operator' => FALSE,
        'label' => '',
      ),
      'id' => 'starter_uid',
      'table' => 'cc_transactions',
      'field' => 'starter_uid',
      'relationship' => 'none',
    ),
    'completer_uid' => array(
      'operator' => '!=',
      'value' => '1',
      'group' => '0',
      'exposed' => FALSE,
      'expose' => array(
        'operator' => FALSE,
        'label' => '',
      ),
      'id' => 'completer_uid',
      'table' => 'cc_transactions',
      'field' => 'completer_uid',
      'relationship' => 'none',
    ),
    'transaction_type' => array(
      'operator' => 'or',
      'value' => array(
        'incoming_direct' => 'incoming_direct',
        'incoming_confirm' => 'incoming_confirm',
        'outgoing_direct' => 'outgoing_direct',
        'outgoing_confirm' => 'outgoing_confirm',
        '3rdparty_direct' => '3rdparty_direct'
      ),
      'group' => '0',
      'exposed' => FALSE,
      'expose' => array(
        'operator' => FALSE,
        'label' => '',
      ),
      'id' => 'transaction_type',
      'table' => 'cc_transactions',
      'field' => 'transaction_type',
      'override' => array(
        'button' => 'Remplacer',
      ),
      'relationship' => 'none',
      'reduce_duplicates' => 0,
    ),
  ));
  $handler->override_option('access', array(
    'type' => 'perm',
    'perm' => 'view all transactions',
  ));
  $handler->override_option('title', t('Transaction list'));
  $handler->override_option('footer_format', '1');
  $handler->override_option('empty', t('There are no transactions in the system yet'));
  $handler->override_option('empty_format', '1');
  $handler->override_option('items_per_page', 50);
  $handler->override_option('use_pager', 'mini');
  $handler->override_option('distinct', 0);
  $handler->override_option('style_plugin', 'table');
  $handler->override_option('style_options', array(
    'grouping' => '',
    'override' => 1,
    'sticky' => 0,
    'order' => 'asc',
    'columns' => array(
      'changed' => 'changed',
      'name_1' => 'name_1',
      'name_2' => 'name_2',
      'title' => 'title',
      'quantity' => 'quantity',
      'cid' => 'cid',
      'quality' => 'quality',
      'state' => 'state',
    ),
    'info' => array(
      'changed' => array(
        'sortable' => 1,
        'separator' => '',
      ),
      'name_1' => array(
        'sortable' => 1,
        'separator' => '',
      ),
      'name_2' => array(
        'sortable' => 1,
        'separator' => '',
      ),
      'title' => array(
        'sortable' => 0,
        'separator' => '',
      ),
      'quantity' => array(
        'separator' => '',
      ),
      'cid' => array(
        'separator' => '',
      ),
      'quality' => array(
        'separator' => '',
      ),
      'state' => array(
        'sortable' => 0,
        'separator' => '',
      ),
    ),
    'default' => '-1',
  ));
  $handler = $view->new_display('page', 'Page', 'page_1');
  $handler->override_option('path', 'transactions');
  $handler->override_option('menu', array(
    'type' => 'normal',
    'title' => t('All transactions'),
    'description' => t('all transactions on the system, with optional argument for currency'),
    'weight' => 20,
    'name' => 'navigation',
  ));
  $handler->override_option('tab_options', array(
    'type' => 'none',
    'title' => '',
    'description' => '',
    'weight' => 0,
  ));
  $handler = $view->new_display('block', 'Block', 'block_1');
  $handler->override_option('fields', array(
    'name_1' => array(
      'label' => '',
      'alter' => array(
        'alter_text' => 0,
        'text' => '',
        'make_link' => 0,
        'path' => '',
        'alt' => '',
        'prefix' => '',
        'suffix' => '',
        'help' => '',
        'trim' => 0,
        'max_length' => '',
        'word_boundary' => 1,
        'ellipsis' => 1,
        'html' => 0,
      ),
      'link_to_user' => 1,
      'overwrite_anonymous' => 0,
      'anonymous_text' => '',
      'exclude' => 0,
      'id' => 'name_1',
      'table' => 'users',
      'field' => 'name',
      'relationship' => 'payer_uid',
      'override' => array(
        'button' => 'Use default',
      ),
    ),
    'quantity' => array(
      'label' => '',
      'alter' => array(
        'alter_text' => 0,
        'text' => '',
        'make_link' => 0,
        'path' => '',
        'alt' => '',
        'prefix' => '',
        'suffix' => '',
        'help' => '',
        'trim' => 0,
        'max_length' => '',
        'word_boundary' => 1,
        'ellipsis' => 1,
        'html' => 0,
      ),
      'exclude' => 0,
      'id' => 'quantity',
      'table' => 'cc_transactions',
      'field' => 'quantity',
      'relationship' => 'none',
      'override' => array(
        'button' => 'Use default',
      ),
    ),
    'name_2' => array(
      'label' => '',
      'alter' => array(
        'alter_text' => 0,
        'text' => '',
        'make_link' => 0,
        'path' => '',
        'alt' => '',
        'prefix' => '',
        'suffix' => '',
        'help' => '',
        'trim' => 0,
        'max_length' => '',
        'word_boundary' => 1,
        'ellipsis' => 1,
        'html' => 0,
      ),
      'link_to_user' => 1,
      'overwrite_anonymous' => 0,
      'anonymous_text' => '',
      'exclude' => 0,
      'id' => 'name_2',
      'table' => 'users',
      'field' => 'name',
      'relationship' => 'payee_uid',
      'override' => array(
        'button' => 'Use default',
      ),
    ),
  ));
  $handler->override_option('items_per_page', 10);
  $handler->override_option('use_pager', '0');
  $handler->override_option('style_plugin', 'list');
  $handler->override_option('style_options', array(
    'grouping' => '',
    'type' => 'ul',
  ));
  $handler->override_option('row_options', array(
    'inline' => array(
      'name_1' => 'name_1',
      'quantity' => 'quantity',
      'name_2' => 'name_2',
    ),
    'separator' => '>',
  ));
  $handler->override_option('block_description', '');
  $handler->override_option('block_caching', -1);
  
  $handler = $view->new_display('feed', 'Flux', 'feed_1');
  $handler->override_option('relationships', array());
  $handler->override_option('arguments', array());
  $handler->override_option('access', array(
    'type' => 'role',
    'role' => array(
      '6' => 6,
    ),
  ));
  $handler->override_option('items_per_page', 0);
  $handler->override_option('style_plugin', 'views_csv');
  $handler->override_option('style_options', array(
    'mission_description' => FALSE,
    'description' => '',
    'filename' => 'transactions.csv',
    'seperator' => ',',
    'quote' => 1,
    'header' => 1,
  ));
  $handler->override_option('row_plugin', '');
  $handler->override_option('path', 'transactions/csv');
  $handler->override_option('menu', array(
    'type' => 'none',
    'title' => '',
    'description' => '',
    'weight' => 0,
    'name' => 'navigation',
  ));
  $handler->override_option('tab_options', array(
    'type' => 'none',
    'title' => '',
    'description' => '',
    'weight' => 0,
  ));
  $handler->override_option('displays', array(
    'page_1' => 'page_1',
    'default' => 0,
    'block_1' => 0,
  ));
  $handler->override_option('sitename_title', FALSE);
  return $view;  
}

function transactions_pending_view() {
  $view = new view;
  $view->name = 'cc_transactions_pending';
  $view->description = 'pending transactions';
  $view->tag = '';
  $view->view_php = '';
  $view->base_table = 'node';
  $view->is_cacheable = FALSE;
  $view->api_version = 2;
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */
  $handler = $view->new_display('default', 'Defaults', 'default');
  $handler->override_option('filters', array(
    'state' => array(
      'operator' => '=',
      'value' => '1',
      'group' => '0',
      'exposed' => FALSE,
      'expose' => array(
        'operator' => FALSE,
        'label' => '',
      ),
      'id' => 'state',
      'table' => 'cc_transactions',
      'field' => 'state',
      'relationship' => 'none',
    ),
  ));
  $handler->override_option('sorts', array(
  'quantity' => array(
    'order' => 'DESC',
    'id' => 'quantity',
    'table' => 'cc_transactions',
    'field' => 'quantity',
    'relationship' => 'none',
  ),
));
  $handler->override_option('access', array(
    'type' => 'none',
  ));
  $handler->override_option('cache', array(
    'type' => 'none',
  ));
  $handler->override_option('empty', '');
  $handler->override_option('empty_format', '1');
  $handler->override_option('items_per_page', 0);
  $handler->override_option('row_plugin', 'node');
  $handler->override_option('row_options', array(
    'relationship' => 'none',
    'build_mode' => 'teaser',
    'links' => 1,
    'comments' => 0,
  ));

  //block to show transactions awaiting confirmation from the user who's page we're on
  $handler = $view->new_display('block', 'my_pending', 'block_my_pending');
  $handler->override_option('arguments', array(
    'completer_uid' => array(
      'default_action' => 'default',
      'style_plugin' => 'default_summary',
      'style_options' => array(),
      'wildcard' => 'all',
      'wildcard_substitution' => 'Tous / Toutes',
      'title' => '',
      'breadcrumb' => '',
      'default_argument_type' => 'current_user',
      'default_argument' => '',
      'validate_type' => 'user',
      'validate_fail' => 'not found',
      'id' => 'completer_uid',
      'table' => 'cc_transactions',
      'field' => 'completer_uid',
      'validate_user_argument_type' => 'uid',
      'override' => array(
        'button' => 'Utiliser les valeurs par défaut',
      ),
      'relationship' => 'none',
      'default_options_div_prefix' => '',
      'default_argument_fixed' => '',
      'default_argument_user' => 0,
      'default_argument_image_size' => '_original',
      'default_argument_php' => '',
      'validate_argument_node_access' => 0,
      'validate_argument_nid_type' => 'nid',
      'validate_argument_type' => 'tid',
      'validate_argument_transform' => 0,
      'validate_argument_php' => '',
      'validate_argument_node_type' => array(
        'image' => 0,
        'community_task' => 0,
        'transaction' => 0,
        'offer' => 0,
        'want' => 0,
        'event' => 0,
        'page' => 0,
        'simplenews' => 0,
        'story' => 0,
      ),
      'validate_argument_vocabulary' => array(
        '2' => 0,
        '1' => 0,
        '4' => 0,
        '3' => 0,
        '5' => 0,
      ),
      'validate_user_restrict_roles' => 0,
      'validate_user_roles' => array(
        '2' => 0,
        '5' => 0,
        '4' => 0,
        '6' => 0,
        '7' => 0,
        '3' => 0,
      ),
      'image_size' => array(
        '_original' => '_original',
        'thumbnail' => 'thumbnail',
        'preview' => 'preview',
      ),
    ),
  ));
  $handler->override_option('access', array(
    'type' => 'perm',
    'perm' => 'view all transactions',
  ));
  $handler->override_option('header', '');
  $handler->override_option('block_description', 'pending transactions for user');
  $handler->override_option('block_caching', -1);

  //block to show all pending transactions relating to the logged in user. Attachment follows
  //this block is also used on the bureau page, where it is passed another user user argument
  $handler = $view->new_display('block', 'User Pending', 'block_user_pending');
  $handler->override_option('arguments', array(
    'completer_uid' => array(
      'default_action' => 'default',
      'style_plugin' => 'default_summary',
      'style_options' => array(),
      'wildcard' => 'all',
      'wildcard_substitution' => 'Tous / Toutes',
      'title' => '',
      'breadcrumb' => '',
      'default_argument_type' => 'user',
      'default_argument' => '',
      'validate_type' => 'user',
      'validate_fail' => 'not found',
      'id' => 'completer_uid',
      'table' => 'cc_transactions',
      'field' => 'completer_uid',
      'validate_user_argument_type' => 'uid',
      'override' => array(
        'button' => 'Utiliser les valeurs par défaut',
      ),
      'relationship' => 'none',
      'default_options_div_prefix' => '',
      'default_argument_fixed' => '',
      'default_argument_user' => 0,
      'default_argument_php' => '',
      'validate_argument_node_access' => 0,
      'validate_argument_nid_type' => 'nid',
      'validate_argument_type' => 'tid',
      'validate_argument_transform' => 0,
      'validate_user_restrict_roles' => 0,
      'validate_argument_php' => '',
      'default_argument_image_size' => '_original',
      'validate_argument_node_type' => array(
        'image' => 0,
        'community_task' => 0,
        'transaction' => 0,
        'offer' => 0,
        'want' => 0,
        'event' => 0,
        'page' => 0,
        'simplenews' => 0,
        'story' => 0,
      ),
      'validate_argument_vocabulary' => array(
        '2' => 0,
        '1' => 0,
        '4' => 0,
        '3' => 0,
        '5' => 0,
      ),
      'validate_user_roles' => array(
        '2' => 0,
        '5' => 0,
        '4' => 0,
        '6' => 0,
        '7' => 0,
        '3' => 0,
      ),
      'image_size' => array(
        '_original' => '_original',
        'thumbnail' => 'thumbnail',
        'preview' => 'preview',
      ),
    ),
  ));
  $handler->override_option('block_description', 'Pending transactions');
  $handler->override_option('block_caching', -1);
  $handler->override_option('header', t('Transactions to sign off'));
  $handler->override_option('header_format', '3');
  $handler->override_option('header_empty', 0);

  $handler = $view->new_display('attachment', 'User waiting', 'attachment_1');
  $handler->override_option('arguments', array(
    'starter_uid' => array(
      'default_action' => 'default',
      'style_plugin' => 'default_summary',
      'style_options' => array(),
      'wildcard' => 'all',
      'wildcard_substitution' => 'Tous / Toutes',
      'title' => '',
      'breadcrumb' => '',
      'default_argument_type' => 'user',
      'default_argument' => '',
      'validate_type' => 'user',
      'validate_fail' => 'not found',
      'id' => 'starter_uid',
      'table' => 'cc_transactions',
      'field' => 'starter_uid',
      'validate_user_argument_type' => 'uid',
      'override' => array(
        'button' => 'Utiliser les valeurs par défaut',
      ),
      'relationship' => 'none',
      'default_options_div_prefix' => '',
      'default_argument_fixed' => '',
      'default_argument_user' => 0,
      'default_argument_image_size' => '_original',
      'default_argument_php' => '',
      'validate_argument_node_access' => 0,
      'validate_argument_nid_type' => 'nid',
      'validate_argument_type' => 'tid',
      'validate_argument_transform' => 0,
      'validate_user_restrict_roles' => 0,
      'validate_argument_php' => '',
      'validate_argument_node_type' => array(
        'image' => 0,
        'community_task' => 0,
        'transaction' => 0,
        'offer' => 0,
        'want' => 0,
        'event' => 0,
        'page' => 0,
        'simplenews' => 0,
        'story' => 0,
      ),
      'validate_argument_vocabulary' => array(
        '2' => 0,
        '1' => 0,
        '4' => 0,
        '3' => 0,
        '5' => 0,
      ),
      'validate_user_roles' => array(
        '2' => 0,
        '5' => 0,
        '4' => 0,
        '6' => 0,
        '7' => 0,
        '3' => 0,
      ),
      'image_size' => array(
        '_original' => '_original',
        'thumbnail' => 'thumbnail',
        'preview' => 'preview',
      ),
    ),
  ));
  $handler->override_option('header', t('Transactions awaiting confirmation'));
  $handler->override_option('empty', '');
  $handler->override_option('attachment_position', 'after');
  $handler->override_option('inherit_arguments', TRUE);
  $handler->override_option('inherit_exposed_filters', FALSE);
  $handler->override_option('inherit_pager', FALSE);
  $handler->override_option('render_pager', TRUE);
  $handler->override_option('displays', array(
    'block_my_pending' => 'block_my_pending',
    'default' => 0,
    'block_user_pending' => 0,
  ));

  return $view;
}

/** 
 * Implementation of hook_views_query_alter
 * remove leading underscores and copy to your custom module
 */
function __transactions_views_query_alter($view, &$query) {
  switch($view->name) {
    //put the names of the views this should act on
    case 'cc_directory_recent_offers':
    case 'cc_directory_recent_wants':
      //usage. Filters nodes according to the role of their creator being shared with the $user's role.
      //this enables communities to be partitioned according to user roles
      global $user;
      $roles = $user->roles;
      unset($roles[DRUPAL_AUTHENTICATED_RID]);
      if ($query->tables['node']['users_roles'] && count($roles)) {
        $query->where[0]['clauses'][] = 'users_roles.rid IN (' . implode(',',array_keys($roles)) .')'; 
      }
  }
}