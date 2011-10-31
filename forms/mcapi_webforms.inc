<?php

/*
 * In this file we build the form, using the object cached in ctools
 */
module_load_include('inc', 'mcapi');

/*
 * main form builder, starts by building the base form in the mcapi module
 */
function mcapi_webforms_form($form, &$form_state, $transaction, $webform, $user = NULL) {
  if (!empty($webform->disabled)) {
    throw new Exception(t('webform @name is disabled', array(
      '@name' => $webform->name,
    )));
  }
  form_load_include($form_state, 'inc', 'mcapi_webforms');
  //only show one currency field 
  $form_state['field']['worth']['und']['items_count'] = 0;
  //reject if the transaction isn't in one of the acceptable states
  //because of a problem rendering checkboxes with value 0, the options were saved using the state names as keys, not the integer constants
  //so we have to look them up again
  $all_states = mcapi_transaction_states();
  if (!in_array($all_states[$transaction->state], $webform->data['workflow']['incoming']) && !$form_state['rebuild']) {
    drupal_set_message(t('Configuration error: this form cannot edit a transaction in state: @state', array('@state' => $transaction->state)));
    return;
  }
  if (empty($form_state['step'])) {
    $form_state['step'] = 1;
    $form_state['storage'] = array();
  }
  if (_is_this_webform($webform)) {
    drupal_set_title($webform->data['step-'.$form_state['step']]['title']);
  }
  if (isset($form_state['#transaction'])) {
    $transaction = $form_state['#transaction'];
  }
  $form = transaction_form($form, $form_state, $transaction);
  if (!$form) return array();
  //remove the label of the 'Worth' field, which is required by field API, but clutter in this context
  //if this were done when the field was constructed (not sure where that is) it would apply to everywhere the field was used
  //if it was done in form alter we would have to check the form_id
  foreach (element_children($form['worth']['und']) as $key) {
    unset($form['worth']['und'][$key]['#title']);
  }

  $form['creator']['#type'] = 'hidden';
  $form['xid']['#type'] = 'hidden';
  $form['type']['#type'] = 'hidden';
  $form['type']['#value'] = $webform->name;
  $form['state']['#type'] = 'hidden';
  $form['state']['#value'] = $webform->data['workflow']['outgoing'];
  
  //particpants
  if ($webform->data['aspect'] == 3) {
    //payer
    $form['payer']['#type'] = strtok($webform->data['payer']['args'], ':');
    $form['payer']['#args'] = array(strtok(':'));
    $form['payer']['#multiple'] = $webform->data['payer']['multiple'];
    //this could be put in the config
    $form['payer']['#me'] = TRUE;
    mcapi_webform_default_value($form['payer'], $webform->data['payer']);
    
    //payee;
    $form['payee']['#type'] = strtok($webform->data['payee']['args'], ':');
    $form['payee']['#args'] = array(strtok(':'));
    //this could be put in the config
    $form['payee']['#me'] = TRUE;
    mcapi_webform_default_value($form['payee'], $webform->data['payee']);

    foreach (array('payer', 'payee') as $participant) {
      if ($webform->data[$participant]['multiple']) {
        $form[$participant]['#multiple'] = TRUE;
        if (isset($form_state['#transactions'])) {//second time around
          $form[$participant]['#default_value'] = $form_state['#transactions'][$participant];
        }
        $form[$participant]['#element_validate'] = array();
        $form['#submit'] = array('mcapi_webforms_multiple_submit'); //replaces default submit handler
        $form['#validate'] = array('mcapi_webforms_multiple_validate');
        break; //because only one can be multiple
      }
    }
  }
  else {
    //I-thou exchanges
    //this section cannot be used to edit existing transactions
    $form['2ndperson'] = array(
      '#default_value' => isset($form_state['values']['2ndperson']) ? $form_state['values']['2ndperson'] : ''
    ) + $form['payee'];
    $form['2ndperson']['#type'] = strtok($webform->data['2ndperson']['args'], ':');
    $form['2ndperson']['#args'] = array(strtok(':'));
    mcapi_webform_default_value($form['2ndperson'], $webform->data['2ndperson']);
    $form['direction'] = array(
      '#type' => $webform->data['direction']['widget'],
      '#options' => array(
        'incoming' => $webform->data['direction']['incoming'],
        'outgoing' => $webform->data['direction']['outgoing']
      )
    );
    //if we are loading the page from blank, or rebuilding the form, we set the default values here,
    mcapi_webform_default_value($form['direction'], $webform->data['direction']);
    //if we are loading the page from blank, or rebuilding the form, we set the default values h
    //however if we are editing an existing transaction,
    //we have to show the transaction owner and derive the direction and partner from the payer and payee
    if ($transaction->xid) {
      if ($transaction->creator == $transaction->payer) {
        $form['2ndperson']['#default_value'] = $transaction->payee;
        $form['direction']['#default_value'] = 'outgoing';
      }
      else{
        $form['2ndperson']['#default_value'] = $transaction->payer;
        $form['direction']['#default_value'] = 'incoming';
      }
    }
    unset($form['payer'], $form['payee']);
    //this validation function must run BEFORE the main one in the API, which requires payer and payee
    array_unshift($form['#validate'], 'mcapi_webforms_validate_2ndperson');
  }
  
  //save button
  if (strpos($webform->data['submission']['button'], '/')) {
    $form['buttons']['submit']['#type'] = 'image_button';
    $form['buttons']['submit']['#src'] = $webform->data['submission']['button'];
  }
  else {
    $form['buttons']['submit']['#value'] = $webform->data['submission']['button'];
  }
  if ($form_state['step'] > 1) {
    $form['buttons']['back'] = array(
      '#type' => 'submit',
      '#value' => t('Back'),
    );
    //the default validate function, transaction_form_validate will rebuild the entity in form_state
    //from the elements in the form, not considering that in step 2 of the web form
    // the transaction elements are not present, but are in form_state storage not form_state values
    $form['#validate'] = array();
  }
  $form['#action'] = url($webform->path);
  //this may help with the validation and submission
  $form_state['#webform'] =  $webform;
  $form['#theme'] = 'mcapi_webform';
  $form['#contextual_links']['mcapi_webforms'] = array('admin/accounting/webforms/list', array($webform->name));
  array_unshift($form['#submit'], 'mcapi_webforms_form_presubmit');
  array_push($form['#submit'], 'mcapi_webforms_form_presubmit');
  $form['#validate'][] = 'mcapi_webforms_validate';
  //custom properties
  //this tells the theme callback which template to use
  $form['#template'] = $webform->data['step-'. $form_state['step']]['template'];
  //this is rather elaborate, but we NOW have to set the type of fields not being used in the template as hidden
  $form['#tokens'] = mcapi_webform_collect_tokens($form_state['#webform']);

  //hide the fields which don't appear as tokens
  foreach ($form['#tokens'] as $fieldname) {
    if (strpos($form['#template'], "[$fieldname]") === FALSE) {
      mcapi_hide_form_children($form[$fieldname]);
    }
  }
  //Only after processing, can we render the tokens and replace them into the template,
  //which is used instead of custom form theming
  $form['#after_build'] = array('mcapi_webform_prepare_tokens');
  return $form;
}

//determine the fields '#default_value' according to the settings on the webform object
//remember that it is already populated from the form_state build arguments
function mcapi_webform_default_value(&$element, $settings) {
  if (!empty($settings['common']['preset'])) {
    $element['#default_value'] = $settings['common']['preset'];
  }
  if (!empty($element['#default_value'])) {
    if (!empty($settings['common']['disable']) == 'disable') {
      $element['#disabled'] = TRUE;
    }
  }
  if (!empty($settings['common']['preset_required']) && !$element['#default_value']) {
    drupal_set_message(t('Default needed for field: @field', array('@field' => $element['#title'])));
  }
  $element['#required'] = intval($settings['common']['required']);
  $element['#theme_wrappers'] = array();
  unset($element['#weight']);
}



function mcapi_webform_collect_tokens(&$webform) {
  $tokens = $webform->data['aspect'] == 3 ? array('payee', 'payer') : array('2ndperson', 'direction');
  //before the children are themed, we need to remove the wrappers from deep within the additional fields.
  $fields = db_query("SELECT field_name FROM {field_config_instance} WHERE bundle = 'transaction' AND deleted = 0")->fetchCol();
  //this picks up 'worth' and any other fields on the transaction entity
  return drupal_map_assoc($tokens) + drupal_map_assoc($fields);
}

function mcapi_webform_prepare_tokens(&$form, &$form_state) {
  $form['#replacements'] = array();
  //this way we only render and replace the fielfds actually used.
  if ($form_state['step'] == 1) {
    foreach ($form['#tokens'] as $fieldname) {
      if (strpos($form['#template'], "[$fieldname]") !== FALSE) {
        mcapi_webform_remove_theme_wrapper($form[$fieldname]);
        $form['#replacements']["[$fieldname]"] = drupal_render($form[$fieldname]);
      }
      //otherwise it was already hidden when the form was built
    }
  }
  else {//2nd stage
    //unset all the fields in the form, having derived their token values
    //all that data is in $form_state['storage'] by now
    foreach ($form['#tokens'] as $token => $fieldname) {
      $form['#replacements'][ '['.$fieldname.']'] = mcapi_element_markup_render($form[$fieldname], $form_state['values']);
      //we can unset the field in step 2 because the transaction has already been stored
      //in $form_state['#transaction'] in validation of step 1
      unset($form[$fieldname]);
    }
  }
  return $form;
}

/*
 * nesting function to hide the form elements within form elements
 */
function mcapi_hide_form_children(&$element) {
  $element['#type'] = 'hidden';
  foreach(element_children($element) as $subfield) {
    mcapi_hide_form_children($element[$subfield]);
  }
}
/*
 * nesting function to remove the outer theme wrapper put on by the element processing.
 */
function mcapi_webform_remove_theme_wrapper(&$field){
  if (in_array($field['#type'], array('checkbox', 'radio'))) {
    return;
  }
  if (isset($field['#theme_wrappers'])) {
    $field['#theme_wrappers'] = array();
  }
  foreach(element_children($field) as $fieldname) {
    if (!empty($field[$fieldname]['#type'])) {
      mcapi_webform_remove_theme_wrapper($field[$fieldname]);
    }
  }
}

/*
 * $variables consists of the usual suspects plus
 * $template
 * $webform_data
 */
function theme_mcapi_webform($variables) {
  $form = &$variables['form'];
  $form['#template'] .= drupal_render_children($form);
  return strtr($form['#template'], $form['#replacements']);
}



/*
 * replace tokens in stage 2 form with text, based on the form item token used in the template
 * this is tricky because we have a formAPI element, and need to pull a sensible text value from it.
 */
function mcapi_element_markup_render($element, $fs_values) {
  switch($element['#type']) {
    case 'hidden':
      return;
    case 'user_chooser_roles':
    case 'user_chooser_permissions':
      return format_username(user_load($fs_values[$element['#name']]));
    case 'radios':
    case 'checkboxes':
    case 'select':
      $val = $fs_values[$element['#name']];
      if (is_array($val)) {
        $val =  implode(', ', $val);
      }
      return $val;
    case 'markup': //in case the form got altered
    case 'textfield':
      return $element['#value'];

    //all the fieldAPI fields appear here as container - the need to be handled quite differently
    case 'container':
      foreach(element_children($element['und']) as $childname) {
        $fieldname = $element['und']['#field_name'];
        $field = field_info_field($fieldname);
        $instance = field_info_instance('transaction', $fieldname, 'transaction');
        $function  = $field['module'] . '_field_formatter_view';
        $renderable[] = $function('transaction', NULL, NULL, $instance, 'und', $fs_values[$fieldname]['und'], $instance['display']['default']);
      }
      return drupal_render($renderable);
    case 'submit':
    case 'token':
      return drupal_render($element);
    default:
      drupal_set_message('Unexpected form element type in mcapi_element_markup_render:'.$element['#type']);
  }
}


/*
 * Validation callback
 * set the payer and payee from the 2ndpeson and direction
 * BEFORE the #transaction object is created in $form_state
 */
function mcapi_webforms_validate_2ndperson($form, &$form_state) {
  global $user;
  $values = &$form_state['values'];
  if ($values['direction'] == 'incoming') {
    $values['payer'] = $values['2ndperson'];
    $values['payee'] = $user->uid;
  }
  else {
    $values['payer'] = $user->uid;
    $values['payee'] = $values['2ndperson'];
  }
}

/*
 * multiple participant form execution callbacks
 * these replace and incorporate the ones on the default form 
 */
function mcapi_webforms_multiple_validate($form, &$form_state) {
  $payers = (array)$form_state['values']['payer'];
  $payees = (array)$form_state['values']['payee'];
  foreach ($payees as $payee) {
    foreach ($payers as $payer) {
      $form_state['values']['payer'] = $payer;
      $form_state['values']['payee'] = $payee;
      $form_state['values']['xid'] = NULL;
      transaction_form_validate($form, $form_state);
      $validatids[] = $form_state['#transaction']->xid;
    }
  }
  //this object will only work in the following submit handler
  $form_state['#transactions'] = array(
    'xids' => $validatids,
    'payer' => $payers,
    'payee' => $payees
  );
}

/*
 * submit callback to replace the default
 */
function mcapi_webforms_multiple_submit($form, &$form_state) {
  $transactions = entity_load('transaction', $form_state['#transactions']['xids']);
  foreach ($transactions as $transaction) {
    $transaction->state = $form_state['#transaction']->state;
    $form_state['#transaction'] = $transaction;
    transaction_all_submit($form, $form_state);
  }
  $message = t('Multiple transaction, ids: @xids', array('@xids' => implode(', ', $form_state['#transactions']['xids'])));
  watchdog('mcapi', $message);
  if (user_access('access site reports') && module_exists('dblog')) {
    $form_state['redirect'] = 'admin/reports/dblog';
  }
  else {
    $form_state['redirect'] = 'transaction/'. $transaction->xid;
  }
}

function mcapi_webforms_validate($form, &$form_state) {
  //don't validate if it's ajax
  if (current_path() == 'system/ajax') {
    return;
  }
  elseif (!empty($form_state['clicked_button']['#submit']) && in_array('mcapi_transaction_erase_confirm_form', $form_state['clicked_button']['#submit'])) {
    return;
  }
  //go back if that button was pressed
  if (isset($form_state['values']['back']) && $form_state['values']['op'] == $form_state['values']['back']) {
    $form_state['rebuild'] = TRUE;
    //$form_state['values'] = $form_state['storage'];
    $form_state['step']--;
  }
  elseif (strlen($form_state['#webform']->data['step-2']['template'])) {
    //Submission errors will not prevent the form advancing.
    $form_state['rebuild'] = $form_state['step'] == 1 ? TRUE : FALSE;
    $form_state['step']++;
  }
  $form_state['storage'] += $form_state['values'];
}

/*
 * First submit callback for default transaction form
 */
function mcapi_webforms_form_presubmit($form, &$form_state) {
  $form_state['values'] = $form_state['storage'];
}
/*
 * Last submit callback for default transaction form
 */
function mcapi_webforms_form_postsubmit($form, &$form_state) {
  //look for the webform object for the redirect
  $form_state['redirect'] = str_replace(
    '#xid',
    $form_state['#transaction']->xid,
    $form_state['#webform']->data['submission']['redirect']
  );
}

//ascertains whether the active path corresponds to this webform
//not very pretty, but we need to know not to set the page title if this form is in a block
function _is_this_webform($webform) {
  $parts = explode('/', current_path());
  if (!empty($parts[2]) && $parts[2] == 'formedit') return TRUE;
  $map = explode('/', $webform->path);
//  debug($parts);debug($map);
  foreach($map as $key => $part) {
    if (strpos($part, '%') === 0) $parts[$key] = $part;
  }
  return $parts == $map;
}