<?php

/**
 * @file rules integration for the mcapi module
 *
 * TODO
 * Make Transaction:payer and transaction:payee (from the passed transaction) appear in the Data selectors
 * Remove the data selector from field worth
 */

/**
 * Implements hook_rules_file_info() on behalf of the mcapi module.
 */
function mcapi_rules_file_info() {
  return array('extras/mcapi.eval');
}
/*
 * implements hook_rules_data_info
 * defines types for actions parameters and for event variables
 */
function mcapi_rules_data_info() {//debug('mcapi_rules_data_info');
  return array(
    //in fact this is just a field API field perhaps it doesn't need defining - 
    'worth' => array(
      'label' => t('Value of transaction'),
      'ui class' => 'RulesDataUIWorth',
      'group' => t('Community Accounting'),
      'token type' => 'transaction'
    ),
    'transaction_cluster' => array(
      'label' => t('A cluster of transactions, with the same serial'),
      'group' => t('Community Accounting'),
    ),
    'transaction_serials' => array(
      'label' => t('An array of serial numbers'),
      'group' => t('Community Accounting'),
    ),
  );
}
//show all the data types
function mcapi_rules_data_info_alter(&$items) {
  //debug(array_keys($items));
}


/**
 * Implements rules_hook_event_info() on behalf of the mcapi module.
 * The rules events are exactly equivalent the triggers defined in hook_trigger_info
 */
function mcapi_rules_event_info() {//debug('mcapi_rules_event_info');
  $cluster = array(
    'label' => t('The volitional transaction, as one of an array'),
    'type' => 'transaction_cluster',
    'skip save' => TRUE //not sure if this is appropriate
  );
  $serials = array(
    'label' => t('An array of serial numbers'),
    'type' => 'transaction_serials',
  );
  $items = array(
    'transactions_alter' => array(
      'label' => t('Modify a transaction cluster before saving'),
      'group' => t('Community accounting'),
      'variables' => array(
        'cluster' => $cluster
      ),
    ),
    'transactions_undone' => array(
      'label' => t('A transaction cluster was undone'),
      'group' => t('Community accounting'),
      'variables' => array(
        'serials' => $serials
      )
    ),
    'transactions_state' => array(
      'label' => t('A transaction cluster changed state'),
      'group' => t('Community accounting'),
      'variables' => array(
        'serials' => $serials,
        'old_state' => array(
          'label' => t('Old state'),
          'type' => 'integer'
        ),
        'new_state' => array(
          'label' => t('New state'),
          'type' => 'integer'
        ),
      ),
    )
  );

  return $items;
}

/**
 * Implements rules_hook_condition_info() on behalf of the mcapi module.
 */
function mcapi_rules_condition_info() {//debug('mcapi_rules_condition_info');
  $items['transaction_is_of_type'] = array(
    'group' => t('Community accounting'),
    'label' => t('Transaction is of type'),
    'help' => t('Evaluates to TRUE if the given transaction is of one of the selected types.'),
    'parameter' => array(
      'type' => array(
        'type' => 'list<text>',
        'label' => t('Transaction types'),
        'options list' => 'mcapi_get_types',
        'description' => t('The transaction types to check for.'),
        'restriction' => 'input',
      )
    ),
  );
  $items['transaction_is_of_state'] = array(
    'group' => t('Community accounting'),
    'label' => t('Transaction is of state'),
    'help' => t('Evaluates to TRUE if the given transaction is in of one of the selected states.'),
    'parameter' => array(
      'type' => array(
        'type' => 'list<text>',
        'label' => t('Transaction states'),
        'options list' => 'mcapi_get_states',
        'description' => t("The transaction states to check for. Note that transaction states may be altered after insertion."),
        'restriction' => 'input',
      )
    ),
  );
  return $items;
}


/**
 * Implements hook_rules_action_info() on behalf of the mcapi module.
 * rules actions are not connected to core actions, but are equivalent
 * These provided are equivalent
 */
function mcapi_rules_action_info() {//debug('mcapi_rules_action_info');
  $items = array(
    //adds a new transaction to the cluster. Only works with event transactions alter.
    'mcapi_dependent_transaction_rules_action' => array(
      'label' => t('Add dependent transaction to cluster being saved'),
      'group' => t('Community accounting'),
      'parameter' => array(
        'worth' => array(
          'label' => t('Value'),
          'type' => 'worth',
        ),
        'payer' => array(
          'label' => t('Payer'),
          'type' => 'user',//ideally restricted to transactees in the given currency, current user, transaction payer, transaction payee
        ),
        'payee' => array(
          'label' => t('Payer'),
          'type' => 'user',//ideally restricted to transactees in the given currency, current user, transaction payer, transaction payee
        ),
      )
    ),
    //generate a mail using transaction variables, if those variables aren't available to the normal mail action
    //'mcapi_mail_rules_action',
    //generate an sms using transaction variables
    //'mcapi_sms_rules_action',
  );

  return $items;
}

/**
 * mcapi integration access callback.
 */
function mcapi_rules_integration_access($type, $name) {
  if ($type == 'event' || $type == 'condition') {
    return entity_access('view', 'mcapi');
  }
}

/**
 * mcapi integration admin access callback.
 */
function mcapi_rules_admin_access() {
  return user_access('configure all currencies');
}

/**
 * UI for configuring worth field
 * This is where we're having real problems
 * The worth field is always attached to the transaction
 * Some actions need to receive a transaction as an argument and create a new transaction by
 */
class RulesDataUIWorth extends RulesDataUI implements RulesDataDirectInputFormInterface {

  public static function getDefaultMode() {
    return array(
      '0' => array(
        'currcode' => 'def_drup',
        'quantity' => 1
      )
    );
  }

  public static function inputForm($name, $info, $settings, RulesPlugin $element) {
    form_load_include($form_state, 'field.inc', 'mcapi');
    _add_rate_fields($form, 3);
    return $form;
  }

  public static function render($value) {debug($value);//i don't know what this is
    return array(
      'content' => array('#markup' => check_plain($value)),
    );
  }
}

//not sure how this is used
function mcapi_rules_evaluator_info() {
  return array(
    'worth_field' => array(
      'class' => 'RulesWorthInputEvaluator',
      'type' => array('worth', 'uri'),
      'weight' => 0,
     ),
  );
}
abstract class RulesWorthInputEvaluator extends RulesDataInputEvaluator {

//  $form = array(
//    param_worth => array(
//      #type => worth_field
//    )
//  )
//  $settings = array(
//    param_worth => array(
        #type => worth_field
//    )
//  )
//  $info = array(
//    label => Worth
//    type => worth
//    description => Field "worth"
//    options list =>
//  )
//  $variables = array(
//    site => array(
//      type => site
//      label => Site information
//      description => Site-wide settings and other global information.
//      property info alter => array(
//        0 => RulesData
//        1 => addSiteMetadata
//      )
//      property info => array()
//      optional => 1
//    )
//    serials => array(
//      label => An array of serial numbers
//      type => transaction_serials
//    )
// )
  //the result of this function seems to be ignored
  public static function attachForm(&$form, $settings, $info, $variables) {
    //no idea what to do here
  }
}


/*
 * NOTES...
 * All this can be sorted out later
 */

define('TRANSACTION_STATE_FORCE_FAILED', -3);
/*
 * implements mcapi hook_info_types
 */
function ___mcapi_rules_mcapi_info_types() {
  return array('auto');
}

/*
 * implements mcapi hook_info_states
 */
function ___mcapi_rules_mcapi_info_states() {
  return array(
    TRANSACTION_STATE_FORCE_FAILED => array(
      'name' => t('Failed'),
      'description' => t('Failed validation but forced through by automated process'),
      'default_access_callbacks' => array('mcapi_access_accountant')
    ),
  );
}

/*
 * wrapper around mcapi API function transactions_save
 * any transaction that fails validation goes into a special state
 */
function ___transaction_auto_save($transactions) {
  $errors = FALSE;
  try {
    transactions_insert($transactions, FALSE);
  }
  catch (Exception $e) {
    foreach ($transactions as $key => $transaction) {
      $transactions[$key]->state = TRANSACTION_STATE_FORCE_FAILED;
      $transactions[$key]->data['fail'] = $e->getMessage();
      $errors[] = $e->getMessage();
    }
  }
  if (!$errors || variable_get('mcapi_rules_error_force', FALSE)) {//this variable is not settable yet
    transactions_insert($transactions, TRUE);
  }
  else {
    drupal_set_message(t('Auto payment was prevented with following messages: !messages'),
      array('!messages' => implode('<br />', $errors)
    ));
    watchdog('mcapi auto', 'Auto payment was prevented with following messages: !messages',
      array('!messages' => implode('<br />', $errors),
      WATCHDOG_ALERT
    ));
  }
}
//
//$entity = entity_create($entity_type_name, $entity_property_value_array);
//$entity->save();