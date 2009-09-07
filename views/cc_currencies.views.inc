<?php
/**
 * @file
 * Contains default views on behalf of the transactions  module.
 */

 /**
 * Implementation of hook_views_data()
 */
function cc_currencies_views_data() {
  $data = array();
  $data['cc_multiple']['table']['group']  = t('Complementary currencies');
  $data['cc_multiple']['table']['base'] = array(
    'title' => t('Currencies'),
    'field' => 'nid',
    'help' => t('Units of account, for trade'),
  );
  $data['cc_multiple']['table']['join'] = array(
    'node' => array(
      'left_field' => 'nid',
      'field' => 'nid',
    ),
  );

  $data['cc_multiple']['icon'] = array(
    'title' => t('Icon'), // The item it appears as on the UI,
    'field' => array(
      'help' => t('The currency icon'),
      'handler' => 'views_handler_field'
    ),
  );
  return $data;
}

function cc_currencies_views_default_views() {
  
$view = new view;
$view->name = 'currency_list';
$view->description = t('list of all currencies');
$view->tag = 'currencies';
$view->view_php = '';
$view->base_table = 'node';
$view->is_cacheable = FALSE;
$view->api_version = 2;
$view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */
$handler = $view->new_display('default', 'Defaults', 'default');
$handler->override_option('fields', array(
  'icon' => array(
    'label' => t('Icon'),
    'alter' => array(
      'alter_text' => 1,
      'text' => '<img src="/[icon]" />',
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
    'exclude' => 0,
    'id' => 'icon',
    'table' => 'cc_multiple',
    'field' => 'icon',
    'relationship' => 'none',
  ),
  'title' => array(
    'label' => t('Name'),
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
  ),
  'name' => array(
    'label' => t('Owner'),
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
    'link_to_user' => 1,
    'overwrite_anonymous' => 1,
    'anonymous_text' => '',
    'exclude' => 0,
    'id' => 'name',
    'table' => 'users',
    'field' => 'name',
    'relationship' => 'none',
    'override' => array(
      'button' => 'Override',
    ),
  ),
  'sticky' => array(
    'label' => t('Universality'),
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
    'type' => 'yes-no',
    'not' => 0,
    'exclude' => 0,
    'id' => 'sticky',
    'table' => 'node',
    'field' => 'sticky',
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
    'relationship' => 'none',
  ),
));
$handler->override_option('filters', array(
  'type' => array(
    'operator' => 'in',
    'value' => array(
      'currency' => 'currency',
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
  'sticky' => array(
    'operator' => '=',
    'value' => '1',
    'group' => '0',
    'exposed' => TRUE,
    'expose' => array(
      'operator' => '',
      'identifier' => 'filter',
      'label' => 'Universal?',
      'optional' => 0,
      'remember' => 0,
    ),
    'id' => 'sticky',
    'table' => 'node',
    'field' => 'sticky',
    'relationship' => 'none',
    'override' => array(
      'button' => 'Override',
    ),
  ),
));
$handler->override_option('access', array(
  'type' => 'none',
));
$handler->override_option('title', t('Public & private currencies'));
$handler->override_option('footer', '<?php   if (user_access(\'create global currencies\') || user_access(\'create meme currencies\'))
   print l(\'Declare a new currency\', \'node/add/currency\'); ?>');
$handler->override_option('footer_format', '1');
$handler->override_option('footer_empty', 0);
$handler->override_option('items_per_page', 25);
$handler->override_option('style_plugin', 'table');
$handler->override_option('style_options', array(
  'grouping' => '',
  'override' => 1,
  'sticky' => 1,
  'order' => 'asc',
  'columns' => array(
    'sticky' => 'sticky',
    'title' => 'title',
    'name' => 'name',
  ),
  'info' => array(
    'sticky' => array(
      'sortable' => 1,
      'separator' => '',
    ),
    'title' => array(
      'sortable' => 1,
      'separator' => '',
    ),
    'name' => array(
      'sortable' => 1,
      'separator' => '',
    ),
  ),
  'default' => '-1',
));
$handler = $view->new_display('page', 'Page', 'page_1');
$handler->override_option('path', 'currencies');
$handler->override_option('menu', array(
  'type' => 'normal',
  'title' => t('Currencies'),
  'description' => 'All the currencies on the system',
  'weight' => '15',
  'name' => 'navigation',
));
$handler->override_option('tab_options', array(
  'type' => 'none',
  'title' => '',
  'description' => '',
  'weight' => 0,
));
  
  
$views[$view->name] = $view;
   
  return $views;
}
