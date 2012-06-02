<?php

/**
 * @file rules integration for the mcapi module
 *
 * @addtogroup rules
 * @{
 */

/**
 * Implements rules_hook_file_info() on behalf of the mcapi module.
 */
function rules_mcapi_file_info() {
  return array('mcapi_auto.eval');
}

/**
 * Implements rules_hook_event_info() on behalf of the mcapi module.
 */
function rules_mcapi_event_info() {ddebug_backtrace();//what is the hook name?
  $items = array(
    'mcapi_insert' => array(
      'label' => t('After recording a new transaction'),
      'group' => t('Community accounting'),
      'variables' => rules_events_mcapi_variables(t('created content')),
      'access callback' => 'rules_mcapi_integration_access',
    ),
    'mcapi_update' => array(
      'label' => t('After updating a transaction'),
      'group' => t('Community accounting'),
      'variables' => rules_events_mcapi_variables(t('updated content'), TRUE),
      'access callback' => 'rules_mcapi_integration_access',
    ),
    'mcapi_delete' => array(
      'label' => t('After deleting a transaction'),
      'group' => t('Community accounting'),
      'variables' => rules_events_mcapi_variables(t('deleted content')),
      'access callback' => 'rules_mcapi_integration_access',
    ),
    'mcapi_state' => array(
      'label' => t('After changing the state of a transaction'),
      'group' => t('Community accounting'),
      'variables' => rules_events_mcapi_variables(t('deleted content')),
      'access callback' => 'rules_mcapi_integration_access',
    ),
  );
  // Specify that on presave the mcapi is saved anyway.
  $items['mcapi_presave']['variables']['mcapi']['skip save'] = TRUE;
  return $items;
}

/**
 * Returns some parameter suitable for using it with a mcapi
 */
function rules_events_mcapi_variables($mcapi_label, $update = FALSE) {
  $args = array(
    'mcapi' => array('type' => 'mcapi', 'label' => $mcapi_label),
  );
  if ($update) {
    $args += array(
      'mcapi_unchanged' => array(
        'type' => 'mcapi',
        'label' => t('unchanged content'),
        'handler' => 'rules_events_entity_unchanged',
      ),
    );
  }
  return $args;
}

/**
 * Implements rules_hook_condition_info() on behalf of the mcapi module.
 */
function rules_mcapi_condition_info() {
  $defaults = array(
    'parameter' => array(
      'mcapi' => array('type' => 'mcapi', 'label' => t('Transaction')),
    ),
    'group' => t('mcapi'),
    'access callback' => 'rules_mcapi_integration_access',
  );
  $items['mcapi_is_of_type'] = array(
    'group' => t('mcapi'),
    'access callback' => 'rules_mcapi_integration_access',
    'label' => t('Transaction is of type'),
    'help' => t('Evaluates to TRUE if the given content is of one of the selected transaction types.'),
    'base' => 'rules_condition_mcapi_is_of_type',
    'parameter' => array(
      'mcapi' => array(
        'type' => 'mcapi',
        'label' => t('Transaction')
      ),
      'type' => array(
        'type' => 'list<text>',
        'label' => t('Transaction types'),
        'options list' => 'mcapi_get_types',
        'description' => t('The transaction types to check for.'),
        'restriction' => 'input',
      )
    ),
  );
  $items['mcapi_is_of_state'] = $defaults + array(
    'label' => t('Transaction is of state'),
    'help' => t('Evaluates to TRUE if the given content is of one of the selected transaction types.'),
    'base' => 'rules_condition_mcapi_is_of_state',
  );
  $items['mcapi_is_of_state']['parameter']['state'] = array(
    'type' => 'list<text>',
    'label' => t('Transaction states'),
    'options list' => 'mcapi_get_states_options',
    'description' => t('The transaction states to check for.'),
    'restriction' => 'input',
  );
  return $items;
}

function mcapi_get_states_options() {
  return mcapi_get_states('#options');
}

/**
 * Provides the content type of a mcapi as asserted metadata.
 */
function rules_condition_mcapi_is_of_type_assertions($element) {
  return array('mcapi' => array('bundle' => $element->settings['type']));
}

/**
 * Implements rules_hook_action_info() on behalf of the mcapi module.
 */
function rules_mcapi_action_info() {
  $defaults = array(
    'parameter' => array(
      'mcapi' => array('type' => 'mcapi', 'label' => t('Transaction'), 'save' => TRUE),
    ),
    'group' => t('mcapi'),
    'access callback' => 'rules_mcapi_admin_access',
  );
  // Add support for hand-picked core actions.
  $core_actions = mcapi_action_info();
  $actions = array(
    'mcapi_insert_action',
    'mcapi_update_action',
    'mcapi_delete_action',
    'mcapi_state_action',
  );
  foreach ($actions as $base) {
    $action_name = str_replace('_action', '', $base);
    $items[$action_name] = $defaults + array(
      'label' => $core_actions[$base]['label'],
      'base' => $base,
    );
  }
  return $items;
}

/**
 * mcapi integration access callback.
 */
function rules_mcapi_integration_access($type, $name) {
  if ($type == 'event' || $type == 'condition') {
    return entity_access('view', 'mcapi');
  }
}

/**
 * mcapi integration admin access callback.
 */
function rules_mcapi_admin_access() {
  return user_access('administer mcapis');
}

/**
 * @}
 */
