<?php
// $Id$

function mc_webforms_views_handlers() {
  $handlers = array(
    'info' => array(
      'path' => drupal_get_path('module', 'mc_webforms') .'/views',
    ),
    'handlers' => array(
      'views_handler_field_node_link_convert_3rdparty_offer' => array(
        'parent' => 'views_handler_field_node_link'
      ),
      'views_handler_field_node_link_convert_3rdparty_want' => array(
        'parent' => 'views_handler_field_node_link'
      ),
    ),
  );
  return $handlers;
}


function mc_webforms_views_data() {
  $data = array();
  $data['node']['convert_node_3rdparty_offer'] = array(
    'field' => array(
      'title' => t('Convert offer to exchange'),
      'help' => t('Populate the thirdparty exchange form, using this node as an offer'),
      'handler' => 'views_handler_field_node_link_convert_3rdparty_offer',
    ),
  );
  $data['node']['convert_node_3rdparty_want'] = array(
    'field' => array(
      'title' => t('Convert want to exchange'),
      'help' => t('Populate the thirdparty exchange form, using this node as a want'),
      'handler' => 'views_handler_field_node_link_convert_3rdparty_want',
    ),
  );
  return $data;
}
