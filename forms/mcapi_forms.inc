<?php

/*
 * In this file we build the form, using the object cached in ctools
 */
module_load_include('inc', 'mcapi');
define('MCAPIFORM_STEP_ENTER', 1);
define('MCAPIFORM_STEP_CONFIRM', 2);

/*
 * main form builder, for this module, using the transaction_form as a base
 * build_info args are 0=> transaction, 1 => mcapi_form
 */
function mcapi_forms_form_mcapi_forms_form_alter(&$form, &$form_state) {
  form_load_include($form_state, 'inc', 'mcapi_forms');
  $mcapi_form = &$form_state['build_info']['args'][1];
  //set the page title if this form is not being displayed in a block
  if (!_mcapi_form_in_block($mcapi_form)) {
    //I wonder if there is an easier way? Perhaps a block would have different build_info args
    drupal_set_title(mcapi_form_template($mcapi_form)->title);
  }
  $transaction = @$form_state['transaction'] ? $form_state['transaction'] : (
    @$form_state['build_info']['args'][0] ? $form_state['build_info']['args'][0] : new_transaction());
  
  //we need to determine how to load up the transaction and which step the form is in.
  //step 1 of the form is a form, and step 2 is a confirmation page. These may be renamed later for clarity
  //Some forms may go straight to step 2
  if (empty($form_state['step'])) {
    $form_state['storage'] = array();
    $form_state['step'] = MCAPIFORM_STEP_ENTER;
    if (empty($mcapi_form->data['step-'.MCAPIFORM_STEP_ENTER]['title'])) {//if we are jumping to confirmation step
      $form_state['step'] = MCAPIFORM_STEP_CONFIRM;
      $form_state['values'] = $form_state['storage'];
    }
    elseif (empty($transaction->xid)) {
      //get the default contents of the fields from the form settings
      foreach (field_info_instances('transaction', 'transaction') as $instance) {
        //The transaction might contain new fields not known about by the saved form
        if (!empty($mcapi_form->data[$instance['field_name']]['shared']['preset'])) {
          $transaction->{$instance['field_name']} = $mcapi_form->data[$instance['field_name']]['shared']['preset'];
        }
      }
    }
  }

  $lang = current(element_children($transaction->worth)); //assumes value is in one language only
  //only show one currency field
  $form_state['field']['worth'][$lang]['items_count'] = 0;

  $form['creator']['#type'] = 'hidden';
  $form['xid']['#type'] = 'hidden';
  $form['type']['#type'] = 'hidden';
  $form['type']['#value'] = $mcapi_form->name;
  
  $form['state']['#value'] = strlen($mcapi_form->data['submission']['state']) ? $mcapi_form->data['submission']['state'] : $transaction->state;
  $form['state']['#type'] = 'hidden';
  //participants
  if ($mcapi_form->data['perspective'] == 3) {
    //payer
    $form['payer']['#type'] = strtok($mcapi_form->data['payer']['args'], ':');
    $form['payer']['#args'] = array(strtok(':'));
    $form['payer']['#multiple'] = $mcapi_form->data['payer']['multiple'];
    //this could be put in the config
    $form['payer']['#me'] = TRUE;
    mcapi_form_default_value($form['payer'], $mcapi_form->data['payer']);
    
    //payee;
    $form['payee']['#type'] = strtok($mcapi_form->data['payee']['args'], ':');
    $form['payee']['#args'] = array(strtok(':'));
    //this could be put in the config
    $form['payee']['#me'] = TRUE;
    mcapi_form_default_value($form['payee'], $mcapi_form->data['payee']);

    foreach (array('payer', 'payee') as $participant) {
      if ($mcapi_form->data[$participant]['multiple']) {
        $form[$participant]['#multiple'] = TRUE;
        if (isset($form_state['transactions'])) {//second time around
          $form[$participant]['#default_value'] = $form_state['transactions'][$participant];
        }
        $form[$participant]['#element_validate'] = array();
        $form['#submit'] = array('mcapi_forms_multiple_submit'); //replaces default submit handler
        $form['#validate'] = array('mcapi_forms_multiple_validate');
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
    $form['2ndperson']['#type'] = strtok($mcapi_form->data['2ndperson']['args'], ':');
    $form['2ndperson']['#args'] = array(strtok(':'));
    $form['2ndperson']['#me'] = FALSE;
    mcapi_form_default_value($form['2ndperson'], $mcapi_form->data['2ndperson']);
    $form['direction'] = _macpi_forms_direction_widget($mcapi_form->data['direction']);
    $form['direction']['#element_validate'][] = 'mcapi_direction_validate';

    //if we are loading the page from blank, or rebuilding the form, we set the default values here,
    mcapi_form_default_value($form['direction'], $mcapi_form->data['direction']);

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
    array_unshift($form['#validate'], 'mcapi_forms_validate_2ndperson');
  }
  //we now modify the transaction form fields according to the form settings for each field
  foreach (field_info_instances('transaction', 'transaction') as $fieldname => $field) {
    //@ needed in case the form builder wasn't saved since the field was added
    $form[$fieldname]['#disabled'] = @$mcapi_form->data[$fieldname]['shared']['disable'];
    $form[$fieldname]['#required'] = @$mcapi_form->data[$fieldname]['shared']['required'];
    mcapi_form_default_value($form[$fieldname], @$mcapi_form->data[$fieldname]);
  }
  //save button
  if (strpos($mcapi_form->data['submission']['button'], '/')) {
    $form['buttons']['submit']['#type'] = 'image_button';
    $form['buttons']['submit']['#src'] = $mcapi_form->data['submission']['button'];
  }
  else {
    $form['buttons']['submit']['#value'] = $mcapi_form->data['submission']['button'];
  }
  if ($form_state['step'] > MCAPIFORM_STEP_CONFIRM && !empty($form_state['values'])) {
    $form['buttons']['back'] = array(
      '#type' => 'submit',
      '#value' => t('Back'),
    );
    //the default validate function, transaction_form_validate will rebuild the entity in form_state
    //from the elements in the form, not considering that in step 2 of the web form
    // the transaction elements are not present, but are in form_state storage not form_state values
    $form['#validate'] = array();
    mcapi_hide_tabs();
  }
  if ($transaction->xid) {
    mcapi_forms_redirect_replace($mcapi_form->data['architecture']['path'], $transaction->xid, $GLOBALS['user']->uid);
  }
  $form['#action'] = url($mcapi_form->data['architecture']['path']);
  //this may help with the validation and submission
  $form['#theme'] = 'mcapi_form';
  $form['#contextual_links']['mcapi_forms'] = array('admin/accounting/forms/list', array($mcapi_form->name));
  array_unshift($form['#submit'], 'mcapi_forms_form_presubmit');
  array_push($form['#submit'], 'mcapi_forms_form_postsubmit');//can't remember why use array_push and not just [] operator
  $form['#validate'][] = 'mcapi_forms_validate';
  //custom properties
  //this tells the theme callback which template to use.
  //Careful the steps in the template may not correspond to the actual steps in the form processing
  //especially if template 1 is blank and the form goes straight to confirmation
  $form['#template'] = mcapi_form_template($mcapi_form, $form_state['step'])->template;
  //this is rather elaborate and early, but we NOW have to set the type of fields not being used in the template as hidden
  $form['#tokens'] = mcapi_form_collect_tokens($mcapi_form);
  //hide the fields which don't appear as tokens
  foreach ($form['#tokens'] as $token => $fieldname) {
    if (strpos($form['#template'], "[$fieldname]") === FALSE) {
      mcapi_element_children_value($form[$fieldname], '#type', 'hidden');
    }
  }
  //Only after processing, can we render the tokens and replace them into the template,
  //which is used instead of custom form theming
  $form['#after_build'] = array('mcapi_form_prepare_tokens');
}

//determine the fields '#default_value' according to the settings on the form object
//remember that it is already populated from the form_state build arguments
function mcapi_form_default_value(&$element, $settings) {
  if (!empty($settings['shared']['preset'])) {
    $element['#default_value'] = $settings['shared']['preset'];
  }
  if (!empty($element['#default_value'])) {
    if (!empty($settings['shared']['disable']) == 'disable') {
      $element['#disabled'] = TRUE;
    }
  }
  if (!empty($settings['shared']['preset_required']) && !$element['#default_value']) {
    drupal_set_message(t('Default needed for field: @field', array('@field' => $element['#title'])));
  }
  if (intval($settings['shared']['required'])) {
    mcapi_element_children_value($element, '#required', TRUE);
  }
  $element['#theme_wrappers'] = array();

  unset($element['#weight']);
}



function mcapi_form_collect_tokens(&$mcapi_form) {
  $tokens = $mcapi_form->data['perspective'] == 3 ? array('payee', 'payer') : array('2ndperson', 'direction');
  //before the children are themed, we need to remove the wrappers from deep within the additional fields.
  $fields = array_keys(field_info_instances('transaction', 'transaction'));
  //this picks up 'worth' and any other fields on the transaction entity
  return drupal_map_assoc($tokens) + drupal_map_assoc($fields);
}

function mcapi_form_prepare_tokens($form, &$form_state) {
  $form['#replacements'] = array();
  if ($form_state['step'] == MCAPIFORM_STEP_ENTER) { //step 1 means we render it as a form
    foreach ($form['#tokens'] as $fieldname) {
      if (strpos($form['#template'], "[$fieldname]") !== FALSE) {
        $element = $form[$fieldname];//make a copy, because removing the title breaks the error handling
        mcapi_form_remove_theme_wrapper($form[$fieldname]);
        mcapi_unset_recursive($element, '#prefix');
        mcapi_unset_recursive($element, '#suffix');
        mcapi_unset_recursive($element, '#title');
        mcapi_unset_recursive($element, '#description');
        $form['#replacements']["[$fieldname]"] = drupal_render($element);
        $form[$fieldname]['#printed'] = TRUE;
      }
      //otherwise it was already hidden when the form was built
    }
  }
  else { //step 2 means we render all the form fields as text
    //unset all the fields in the form, having derived their token values
    //all that data is in $form_state['storage'] by now
    foreach ($form['#tokens'] as $token => $fieldname) {
      $form['#replacements'][ '['.$fieldname.']'] = mcapi_element_markup_render($form[$fieldname], $form_state['values']);
      //we can unset the field in step 2 because the transaction has already been stored
      //in $form_state['transaction'] in validation of step 1
    }
  }
  return $form;
}

/*
 * $variables consists of the usual suspects plus
 * $template
 * $mcapi_form_data
 */
function theme_mcapi_form($variables) {
  $form = &$variables['form'];
  $form['#template'] .= drupal_render_children($form);
  return strtr($form['#template'], $form['#replacements']);
}

/*
 * replace tokens in stage 2 form with text, based on the form item token used in the template
 * this is tricky because we have a formAPI element, and need to pull a sensible text value from it.
 */
function mcapi_element_markup_render(&$element, $fs_values) {
  $element['#printed'] = TRUE;
  switch($element['#type']) {
    case 'hidden':
      return;
    case 'user_chooser_roles':
    case 'user_chooser_permissions':
      $uids = is_array($fs_values[$element['#name']]) ? $fs_values[$element['#name']] : array($fs_values[$element['#name']]);
      return mcapi_format_usernames(user_load_multiple($uids));
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

    //all the fieldAPI fields appear here as container, here we take the form fields and convert them to a render array
    case 'container':
      $lang = current(element_children($element)); //assumes value is in one language only
      foreach(element_children($element[$lang]) as $childname) {
        $fieldname = $element[$lang]['#field_name'];
        $field = field_info_field($fieldname);
        $instance = field_info_instance('transaction', $fieldname, 'transaction');
        $function  = $field['module'] . '_field_formatter_view';
        $renderable[] = $function('transaction', NULL, NULL, $instance, $lang, $fs_values[$fieldname][$lang], $instance['display']['default']);
      }
      return drupal_render($renderable);
    case 'submit':
    case 'token':
      return drupal_render($element);
    default:
      drupal_set_message('Unexpected form element type in mcapi_element_markup_render: '.$element['#type'] , 'error');
  }
}


/*
 * Validation callback
 * set the payer and payee from the 2ndpeson and direction
 * BEFORE $form_state['transaction'] is created
 */
function mcapi_forms_validate_2ndperson($form, &$form_state) {
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
function mcapi_forms_multiple_validate($form, &$form_state) {
  $payers = (array)$form_state['values']['payer'];
  $payees = (array)$form_state['values']['payee'];
  foreach ($payees as $payee) {
    foreach ($payers as $payer) {
      $form_state['values']['payer'] = $payer;
      $form_state['values']['payee'] = $payee;
      $form_state['values']['xid'] = NULL;
      transaction_form_validate($form, $form_state);
      $validatids[] = $form_state['transaction']->xid;
    }
  }
  //this object will only work in the following submit handler
  $form_state['transactions'] = array(
    'xids' => $validatids,
    'payer' => $payers,
    'payee' => $payees
  );
}

/*
 * submit callback to replace the default
 */
function mcapi_forms_multiple_submit($form, &$form_state) {
  $transactions = entity_load('transaction', $form_state['transactions']['xids']);
  foreach ($transactions as $transaction) {
    $transaction->state = $form_state['transaction']->state;
    $form_state['transaction'] = $transaction;
    transaction_all_submit($form, $form_state);
  }
  watchdog('mcapi', 'Multiple transaction, ids: @xids', array('@xids' => implode(', ', $form_state['transactions']['xids'])));
  if (user_access('access site reports') && module_exists('dblog')) {
    $form_state['redirect'] = 'admin/reports/dblog';
  }
  else {
    $form_state['redirect'] = 'transaction/'. $transaction->xid;
  }
}

function mcapi_forms_validate($form, &$form_state) {
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
    $form_state['step']--;
  }
  //if step 2 template is available add a step and rebuild the form
  elseif (strlen($form_state['build_info']['args'][1]->data['step-'.MCAPIFORM_STEP_CONFIRM]['template'])) {
    if (!form_get_errors()) {
      //Submission errors will not prevent the form advancing.
      $form_state['rebuild'] = $form_state['step'] == MCAPIFORM_STEP_ENTER ? TRUE : FALSE;
      $form_state['step']++;
    }
  }
  $form_state['storage'] += $form_state['values'];
}

/*
 * First submit callback for default transaction form
 */
function mcapi_forms_form_presubmit($form, &$form_state) {
  $form_state['values'] = $form_state['storage'];
}
/*
 * Last submit callback for default transaction form
 * this has to be after the transaction id is created and before the end of form processing
 */
function mcapi_forms_form_postsubmit($form, &$form_state) {
  //look for the mcapi_form object for the redirect
  mcapi_forms_redirect_replace($form_state['redirect'], $form_state['transaction']->xid, $GLOBALS['user']->uid);
}
function mcapi_forms_redirect_replace(&$url, $xid, $uid) {
  $url = str_replace(array('%transaction', '%user'), array($xid, $uid), $url);
}

//ascertains whether the active path corresponds to this mcapi_form
//not very pretty, but we need to know not to set the page title if this form is in a block
function _mcapi_form_in_block($mcapi_form) {
  $parts = explode('/', current_path());
  if (!empty($parts[2]) && $parts[2] == 'formedit') return FALSE;
  //reverse calculate the path
  $map = explode('/', $mcapi_form->data['architecture']['path']);
  foreach($map as $key => $part) {
    if (strpos($part, '%') === 0) $parts[$key] = $part;
  }
  return $parts != $map;
}

function _macpi_forms_direction_widget($data) {
  return array(
    '#type' => $data['widget'] ? $data['widget'] : 'select',
    '#options' => array(
      'incoming' => $data['incoming'],
      'outgoing' => $data['outgoing']
    )
  );
}
function mcapi_direction_validate(&$element, &$form_state) {
  if (empty($element['#value'])) {
    //can't do usual form_error things because we are stripping the theme wrappers from the form elements to make the templating work better
    drupal_set_message(t('Which way is the transaction going?'), 'error');
  }
}


//The following recursive functions aren't very elegant, but I can't 'find a more drupalish way of doing them
/*
 * nesting function to set values wherever in the element
 */
function mcapi_element_children_value(&$element, $name, $value) {
  $children = element_children($element);
  if (count($children)) {
    foreach($children as $child) {
      mcapi_element_children_value($element[$child], $name, $value);
    }
  }
  else {
    $element[$name] = $value;
  }
}
/*
 * nesting function to remove the outer theme wrapper put on by the element processing.
 */
function mcapi_form_remove_theme_wrapper(&$field){
  if (in_array($field['#type'], array('checkbox', 'radio'))) {
    return;
  }
  if (isset($field['#theme_wrappers'])) {
    $field['#theme_wrappers'] = array();
  }
  foreach(element_children($field) as $fieldname) {
    if (!empty($field[$fieldname]['#type'])) {
      mcapi_form_remove_theme_wrapper($field[$fieldname]);
    }
  }
}
function mcapi_unset_recursive(array &$array, $prop) {
  unset($array[$prop]);
  foreach($array as $key => &$val) {
    if (is_array($val)) {
      mcapi_unset_recursive($val, $prop);
    }
  }
}
