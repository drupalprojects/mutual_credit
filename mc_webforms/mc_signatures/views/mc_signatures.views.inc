<?php
// $Id$

/**
 * Exposes the signatories to views
 */

function mc_signatures_views_handlers() {
  $handlers= array(
    'info' => array(
      'path' => drupal_get_path('module', 'mc_signatures') .'/views',
    ),
    'handlers' => array(
      'views_handler_field_mc_signatories' => array(
        'parent' => 'views_handler_field'
      ),
      'views_handler_argument_mc_signatures' => array(
        'parent' => 'views_handler_argument'
      ),
    ),
  );
  return $handlers;
}

function mc_signatures_views_data() {
  $data = array();
  $data['mc_unsigned']['table']['group']  = t('Exchanges');
  $data['mc_unsigned']['table']['join'] = array(
    'users' => array(
      'left_field' => 'uid',
      'field' => 'uid',
    ),
    'node' => array(
      'left_field' => 'nid',
      'field' => 'nid',
    ),
  );

    // link to edit comment
  $data['mc_unsigned']['signatories'] = array(
    'field' => array(
      'title' => t('Signatories'),
      'help' => t('Provide a simple link to edit the comment.'),
      'handler' => 'views_handler_field_mc_signatories',
    ),
  );

  $data['mc_unsigned']['uid'] = array(
    'help' => t('Users needing to sign exchange'),
    'argument' => array(
      'title' => t('Signature needed from user'),
      'handler' => 'views_handler_argument_user_uid',
      'numeric' => TRUE,
    ),
    // Works for admin. Otherwise query & results are good, but process doesn't reach render
    // could it be permissions? mcapi access is not called.
    'filter' => array(
      'title' => t('My signatures'),
      'handler' => 'views_handler_filter_user_current',
      'numeric' => TRUE,
    ),
  );
  return $data;
}

function mc_signatures_views_default_views() {
  $view = new view;
  $view->name = 'mc_exchanges_pending';
  $view->description = t('pending exchanges, and their signatories');
  $view->tag = 'exchanges';
  $view->view_php = '';
  $view->base_table = 'node';
  $view->is_cacheable = FALSE;
  $view->api_version = 2;
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */
  $handler = $view->new_display('default', 'Defaults', 'default');
  $handler->override_option('fields', array(
    'title' => array(
      'label' => '',
      'alter' => array(
        'alter_text' => 0,
        'text' => '',
        'make_link' => 0,
        'path' => '',
        'link_class' => '',
        'alt' => '',
        'prefix' => '',
        'suffix' => '',
        'target' => '',
        'help' => '',
        'trim' => 0,
        'max_length' => '',
        'word_boundary' => 1,
        'ellipsis' => 1,
        'html' => 0,
        'strip_tags' => 0,
      ),
      'empty' => '',
      'hide_empty' => 0,
      'empty_zero' => 0,
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
    'cid' => array(
      'label' => 'Currency ID',
      'alter' => array(
        'alter_text' => 0,
        'text' => '',
        'make_link' => 0,
        'path' => '',
        'link_class' => '',
        'alt' => '',
        'prefix' => '',
        'suffix' => '',
        'target' => '',
        'help' => '',
        'trim' => 0,
        'max_length' => '',
        'word_boundary' => 1,
        'ellipsis' => 1,
        'html' => 0,
        'strip_tags' => 0,
      ),
      'empty' => '',
      'hide_empty' => 0,
      'empty_zero' => 0,
      'exclude' => 1,
      'id' => 'cid',
      'table' => 'mc_exchanges',
      'field' => 'cid',
      'relationship' => 'none',
    ),
    'quantity' => array(
      'label' => '',
      'alter' => array(
        'alter_text' => 0,
        'text' => '',
        'make_link' => 0,
        'path' => '',
        'link_class' => '',
        'alt' => '',
        'prefix' => '',
        'suffix' => '',
        'target' => '',
        'help' => '',
        'trim' => 0,
        'max_length' => '',
        'word_boundary' => 1,
        'ellipsis' => 1,
        'html' => 0,
        'strip_tags' => 0,
      ),
      'empty' => '',
      'hide_empty' => 0,
      'empty_zero' => 0,
      'exclude' => 0,
      'id' => 'quantity',
      'table' => 'mc_exchanges',
      'field' => 'quantity',
      'relationship' => 'none',
      'override' => array(
        'button' => 'Override',
      ),
    ),
    'signatories' => array(
      'label' => '',
      'alter' => array(
        'alter_text' => 0,
        'text' => '',
        'make_link' => 0,
        'path' => '',
        'link_class' => '',
        'alt' => '',
        'prefix' => '',
        'suffix' => '',
        'target' => '',
        'help' => '',
        'trim' => 0,
        'max_length' => '',
        'word_boundary' => 1,
        'ellipsis' => 1,
        'html' => 0,
        'strip_tags' => 0,
      ),
      'empty' => '',
      'hide_empty' => 0,
      'empty_zero' => 0,
      'exclude' => 0,
      'id' => 'signatories',
      'table' => 'mc_unsigned',
      'field' => 'signatories',
      'relationship' => 'none',
    ),
  ));
  $handler->override_option('sorts', array(
    'quantity' => array(
      'order' => 'DESC',
      'id' => 'quantity',
      'table' => 'mc_exchanges',
      'field' => 'quantity',
      'relationship' => 'none',
    ),
  ));
  $handler->override_option('arguments', array(
    'uid' => array(
      'default_action' => 'default',
      'style_plugin' => 'default_summary',
      'style_options' => array(),
      'wildcard' => 'all',
      'wildcard_substitution' => 'All',
      'title' => '',
      'breadcrumb' => '',
      'default_argument_type' => 'user',
      'default_argument' => '',
      'validate_type' => 'user',
      'validate_fail' => 'not found',
      'break_phrase' => 0,
      'not' => 0,
      'id' => 'uid',
      'table' => 'mc_unsigned',
      'field' => 'uid',
      'validate_user_argument_type' => 'uid',
      'validate_user_roles' => array(
        '2' => 0,
      ),
      'relationship' => 'none',
      'default_options_div_prefix' => '',
      'default_argument_fixed' => '',
      'default_argument_user' => 0,
      'validate_argument_node_type' => array(
        'currency' => 0,
        'exchange' => 0,
        'page' => 0,
        'story' => 0,
      ),
      'validate_argument_node_access' => 0,
      'validate_argument_nid_type' => 'nid',
      'validate_argument_vocabulary' => array(),
      'validate_argument_type' => 'tid',
      'validate_argument_transform' => 0,
      'validate_user_restrict_roles' => 0,
      'override' => array(
        'button' => 'Override',
      ),
    ),
  ));
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
      'table' => 'mc_exchanges',
      'field' => 'state',
      'relationship' => 'none',
    ),
  ));
  $handler->override_option('access', array(
    'type' => 'none',
  ));
  $handler->override_option('cache', array(
    'type' => 'none',
  ));
  $handler->override_option('items_per_page', 0);
  $handler->override_option('row_options', array(
    'inline' => array(
      'title' => 'title',
      'quantity' => 'quantity',
      'cid' => 'cid',
      'signatories' => 'signatories',
    ),
    'separator' => '',
    'hide_empty' => 0,
  ));

  $handler = $view->new_display('block', t('I must sign'), 'block_1');
  $handler->override_option('arguments', array(
    'uid' => array(
      'default_action' => 'default',
      'style_plugin' => 'default_summary',
      'style_options' => array(),
      'wildcard' => 'all',
      'wildcard_substitution' => 'All',
      'title' => '',
      'breadcrumb' => '',
      'default_argument_type' => 'current_user',
      'default_argument' => '',
      'validate_type' => 'user',
      'validate_fail' => 'not found',
      'break_phrase' => 0,
      'not' => 0,
      'id' => 'uid',
      'table' => 'mc_unsigned',
      'field' => 'uid',
      'validate_user_argument_type' => 'uid',
      'validate_user_roles' => array(
        '2' => 0,
      ),
      'relationship' => 'none',
      'default_options_div_prefix' => '',
      'default_argument_fixed' => '',
      'default_argument_user' => 0,
      'validate_argument_node_type' => array(
        'currency' => 0,
        'exchange' => 0,
        'page' => 0,
        'story' => 0,
      ),
      'validate_argument_node_access' => 0,
      'validate_argument_nid_type' => 'nid',
      'validate_argument_vocabulary' => array(),
      'validate_argument_type' => 'tid',
      'validate_argument_transform' => 0,
      'validate_user_restrict_roles' => 0,
      'override' => array(
        'button' => 'Use default',
      ),
    ),
  ));
  $handler->override_option('block_description', t('I must sign'));
  $handler->override_option('block_caching', -1);

  $handler = $view->new_display('block', t('User must sign'), 'block_2');
  $handler->override_option('block_description', t('User must sign'));
  $handler->override_option('block_caching', -1);

  $handler = $view->new_display('block', t('User waiting on'), 'block_3');
  $handler->override_option('arguments', array(
    'uid_touch' => array(
      'default_action' => 'default',
      'style_plugin' => 'default_summary',
      'style_options' => array(),
      'wildcard' => 'all',
      'wildcard_substitution' => 'All',
      'title' => '',
      'breadcrumb' => '',
      'default_argument_type' => 'user',
      'default_argument' => '',
      'validate_type' => 'user',
      'validate_fail' => 'not found',
      'id' => 'uid_touch',
      'table' => 'node',
      'field' => 'uid_touch',
      'validate_user_argument_type' => 'uid',
      'validate_user_roles' => array(
        '2' => 0,
      ),
      'override' => array(
        'button' => 'Use default',
      ),
      'relationship' => 'none',
      'default_options_div_prefix' => '',
      'default_argument_fixed' => '',
      'default_argument_user' => 0,
      'default_argument_php' => '',
      'validate_argument_node_type' => array(
        'currency' => 0,
        'exchange' => 0,
        'page' => 0,
        'story' => 0,
      ),
      'validate_argument_node_access' => 0,
      'validate_argument_nid_type' => 'nid',
      'validate_argument_vocabulary' => array(),
      'validate_argument_type' => 'tid',
      'validate_argument_transform' => 0,
      'validate_user_restrict_roles' => 0,
      'validate_argument_php' => '',
    ),
  ));
  $handler->override_option('block_description', t('user waiting on'));
  $handler->override_option('block_caching', -1);
  $handler = $view->new_display('page', t('All pending'), 'page_1');
  $handler->override_option('fields', array(
    'created' => array(
      'id' => 'created',
      'table' => 'node',
      'field' => 'created',
    ),
    'title' => array(
      'label' => '',
      'alter' => array(
        'alter_text' => 0,
        'text' => '',
        'make_link' => 0,
        'path' => '',
        'link_class' => '',
        'alt' => '',
        'prefix' => '',
        'suffix' => '',
        'target' => '',
        'help' => '',
        'trim' => 0,
        'max_length' => '',
        'word_boundary' => 1,
        'ellipsis' => 1,
        'html' => 0,
        'strip_tags' => 0,
      ),
      'empty' => '',
      'hide_empty' => 0,
      'empty_zero' => 0,
      'link_to_node' => 1,
      'exclude' => 0,
      'id' => 'title',
      'table' => 'node',
      'field' => 'title',
      'relationship' => 'none',
    ),
    'quantity' => array(
      'label' => t('Amount'),
      'alter' => array(
        'alter_text' => 0,
        'text' => '',
        'make_link' => 0,
        'path' => '',
        'link_class' => '',
        'alt' => '',
        'prefix' => '',
        'suffix' => '',
        'target' => '',
        'help' => '',
        'trim' => 0,
        'max_length' => '',
        'word_boundary' => 1,
        'ellipsis' => 1,
        'html' => 0,
        'strip_tags' => 0,
      ),
      'empty' => '',
      'hide_empty' => 0,
      'empty_zero' => 0,
      'exclude' => 0,
      'id' => 'quantity',
      'table' => 'mc_exchanges',
      'field' => 'quantity',
      'override' => array(
        'button' => 'Use default',
      ),
      'relationship' => 'none',
    ),
    'signatories' => array(
      'label' => '',
      'alter' => array(
        'alter_text' => 0,
        'text' => '',
        'make_link' => 0,
        'path' => '',
        'link_class' => '',
        'alt' => '',
        'prefix' => '',
        'suffix' => '',
        'target' => '',
        'help' => '',
        'trim' => 0,
        'max_length' => '',
        'word_boundary' => 1,
        'ellipsis' => 1,
        'html' => 0,
        'strip_tags' => 0,
      ),
      'empty' => '',
      'hide_empty' => 0,
      'empty_zero' => 0,
      'exclude' => 0,
      'id' => 'signatories',
      'table' => 'mc_unsigned',
      'field' => 'signatories',
      'relationship' => 'none',
    ),
  ));
  $handler->override_option('sorts', array(
    'created' => array(
      'order' => 'DESC',
      'granularity' => 'second',
      'id' => 'created',
      'table' => 'node',
      'field' => 'created',
      'override' => array(
        'button' => 'Use default',
      ),
      'relationship' => 'none',
    ),
  ));
  $handler->override_option('arguments', array());
  $handler->override_option('access', array(
    'type' => 'perm',
    'perm' => 'view all exchanges',
  ));
  $handler->override_option('style_plugin', 'table');
  $handler->override_option('style_options', array(
    'grouping' => '',
    'override' => 1,
    'sticky' => 0,
    'order' => 'asc',
    'columns' => array(
      'created' => 'created',
      'title' => 'title',
      'quantity' => 'quantity',
      'signatories' => 'signatories',
    ),
    'info' => array(
      'created' => array(
        'sortable' => 1,
        'separator' => '',
      ),
      'title' => array(
        'sortable' => 0,
        'separator' => '',
      ),
      'quantity' => array(
        'sortable' => 1,
        'separator' => '',
      ),
      'signatories' => array(
        'separator' => '',
      ),
    ),
    'default' => '-1',
  ));
  $handler->override_option('row_options', array(
    'inline' => array(),
    'separator' => '',
    'hide_empty' => 0,
  ));
  $handler->override_option('path', 'unsigned');
  $handler->override_option('menu', array(
    'type' => 'normal',
    'title' => t('Unsigned exchanges'),
    'description' => '',
    'weight' => '0',
    'name' => 'navigation',
  ));
  $handler->override_option('tab_options', array(
    'type' => 'none',
    'title' => '',
    'description' => '',
    'weight' => 0,
    'name' => 'navigation',
  ));

  return array($view->name => $view);
}
