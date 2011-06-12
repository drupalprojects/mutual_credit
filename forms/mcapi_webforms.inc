<?php

/*
 * In this file we build the form, using the object cached in ctools
 */
module_load_include('inc', 'mcapi');

/*
 * main form builder, starts by building the base form in the mcapi module
 */
function mcapi_webforms_form($form, &$form_state, $transaction, $webform, $user = NULL) {
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
  $form['creator']['#type'] = 'hidden';
  $form['xid']['#type'] = 'hidden';
  $form['type']['#type'] = 'hidden';
  $form['type']['#value'] = $webform->name;
  $form['state']['#type'] = 'hidden';
  $form['state']['#value'] = $webform->data['workflow']['outgoing'];
  
  //currency ID
  mcapi_webform_default_value($form['cid'], $webform->data['cid']);
  //otherwise empty radio buttons aren't validated;
  //$form['cid']['#needs_validation'] = TRUE;
  //if the ID is 'given' or if there's only one currency available, then this is a single currency form, and this widget isn't shown
  //if more than one currency is checked and the value was not preset in the form builder, show the field
  $currencies = currencies_load();
  if (count($currencies) > 1) {
    //if any currencies were selected in the settings, then filter out any that weren't
    if (array_filter($webform->data['cid']['options'])){
      foreach ($webform->data['cid']['options'] as $cid => $checked) {
        if (!$checked) unset($currencies[$cid]);
      }
    }
    //prepare the options array
    foreach ($currencies as $cid => $currency) {
      $cids[$cid] = $currency->name;
    }
    if (empty($webform->data['cid']['preset']) && count($cids) > 1) {
      $form['cid']['#type'] = $webform->data['cid']['widget'];
      $form['cid']['#options'] = $cids;
      $form['cid']['#ajax'] = array(
        'callback' => 'mcapi_webforms_ajax_amount',
        'wrapper' => $webform->name .'-amount',
      );
    }
  }
  if(!isset($form['cid']['#options'])) {
    $form['cid']['#type'] = 'hidden';
    $form['cid']['#value'] = isset($webform->data['cid']['preset']) ? $webform->data['cid']['preset'] : key($currencies);
  }
  //to avoid the theme_wrappers being put on later in form_pre_render_conditional_form_element()
  unset($form['cid']['#title'], $form['cid']['#description']);
 
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
  
  //amount is two subfields, integer and division, and replaces quantity element
  unset($form['quantity']);
  $form['amount'] = array();
  //the mcapi_amount element type writes a 'quantity' float back into the form_state
  //when we declare it here we are pretending it's a normal field, but in the processing, it becomes 2 fields
  $form['amount']['#type'] = 'mcapi_amount';
  $form['amount']['#default_value'] = $transaction->quantity;
  $form['amount']['#chars'] =  !empty($webform->data['amount']['chars']) ? $webform->data['amount']['chars'] : 6;
  mcapi_webform_default_value($form['amount'], $webform->data['amount']);
  $form['amount']['#id'] = $webform->name .'-amount';
  $form['amount']['#cid'] = $form['cid']['#default_value'];
  $form['#prefix'] = "<style>.amount div{display:inline;}</style>";
  //description
  mcapi_webform_default_value($form['description'], $webform->data['description']);
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
  }
  //this may help with the validation and submission
  $form_state['#webform'] =  $webform;
  $form['#theme'] = 'mcapi_webform';
  $form['#contextual_links']['mcapi_webforms'] = array('admin/accounting/webforms/list', array($webform->name));
  array_unshift($form['#submit'], 'mcapi_webforms_form_presubmit');
  $form['#validate'][] = 'mcapi_webforms_validate';
  //custom properties
  //this tells the theme callback which template to use
  $form['#template'] = $webform->data['step-'. $form_state['step']]['template'];
  //this is rather elaborate, but we NOW have to set the type of fields not being used in the template as hidden
  $form['#tokens'] = mcapi_webform_collect_tokens($form, $form_state['#webform']);
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



function mcapi_webform_collect_tokens(&$form, &$webform) {
  $tokens = array('cid', 'description', 'amount');
  if ($webform->data['aspect'] == 3) {
    $tokens[] = 'payee';
    $tokens[] = 'payer';
  }
  else {
    $tokens[] = 'direction';
    $tokens[] = '2ndperson';
  }
  //before the children are themed, we need to remove the wrappers from deep within the additional fields.
  foreach (mcapi_webform_get_additional_form_fields() as $fieldname => $field) {
    $tokens[$fieldname] = $fieldname;
  }
  return $tokens;
}

function mcapi_webform_prepare_tokens(&$form, &$form_state) {
  $form['#replacements'] = array();
  //this way we only render and replace the fields actually used.
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
      $form['#replacements'][ '['.$fieldname.']'] = mcapi_element_markup_render($form[$fieldname]);
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
    mcapi_webform_remove_theme_wrapper($field[$fieldname]);
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
 * replace form elements with renderable text
 */
function mcapi_element_markup_render($element) {
  if (!isset($element['#type']) || $element['#type'] == 'container') {
    return mcapi_element_markup_render(_get_deepest_child($element));
  }
  switch($element['#type']) {
    case 'textfield':
      return $element['#value'];
    case 'mcapi_amount':
      return mcapi_amount_value($element);
    case 'user_chooser_roles':
    case 'user_chooser_permissions':
      if (!isset($element['#options'])) {
        debug($element);
      }
      //on to the next
    case 'radios':
    case 'checkboxes':
    case 'select':
      if (!is_array($element['#value'])) {
        return $element['#options'][$element['#value']];
      }
      else {
        $vals = array();
        foreach ($element['#value'] as $key) {
          $vals[] = $element['#options'][$key];
        }
        return implode(', ', $vals);
      }
      break;
    case 'submit':
    case 'token':
      return drupal_render($element);
    case 'hidden':
      return;
    default:
      drupal_set_message('Unknown form element in mcapi_element_markup_render:'.$element['#type']);
  }
}




/*
 * Functions for element 'amount'
 */

/*
 * field processor callback, declared in mcapi_webforms_element_info
 */
function form_process_mcapi_amount($element, $form_state, $form) {
  $element['#tree'] = TRUE;
  $size = isset($element['#chars']) ? $element['#chars'] : 4;
  $element['integer'] = array(
    '#title' => t('Units'), //this is only ever seen on the error message anyway
    '#type' => 'textfield',
    '#default_value' => isset($element['#default_value']) ? intval($element['#default_value']) : 0,
    '#size' => $size,
    '#maxlength' => $size,
    '#theme_wrappers' => array(),
  );
  if (!empty($form_state['values']['cid'])) {
    $cid = $form_state['values']['cid'];
  }
  elseif(!empty($form_state['build_info']['args'][1]->cid)) {
    $cid = $form_state['build_info']['args'][1]->cid;
  }
  if (isset($cid)) {
    $currency = currency_load($cid);
    $element['#cid'] = $cid;
    //if any currency on the form has subdivisions then output this field
    if (!empty($currency->divisions_conf)) {
      $element['division'] = array(
        '#title' => t('Fraction'),
        '#default_value' => isset($element['#default_value']) ? str_pad(fmod($element['#default_value'], 1) * 100, 2, '0', STR_PAD_LEFT) : 0,
        '#theme_wrappers' => array(),
      );
      if ($currency->divisions_conf == '00') {
        $element['division'] +=  array(
          '#type' => 'textfield',
          '#size' => 2,
          '#maxlength' => 2,
          '#field_prefix' => '.',
        );
      }
      else{
        $element['division'] += array(
          '#options' => $currency->divisions,
          '#type' => 'select',
          '#field_prefix' => ':',
        );
      }
    }
  }
  $element['#element_validate'] = array('validate_mcapi_amount_field');
  //we need to tell the theming function what the currency is, if we know
  return $element;
}

/*
 * element validation callback
 */
function validate_mcapi_amount_field(&$element, &$form_state, $form) {
  $division = NULL;
  //should be numeric, an integer & positive
  foreach (array('integer', 'division') as $subfield) {
    if (isset($element[$subfield]['#value'])) {
      $$subfield = $element[$subfield]['#value'] + 0;
      if ($$subfield) {
        if (!is_numeric($element[$subfield]['#value'])) {
          form_error($element[$subfield], t("Only numbers allowed for '@fieldname'", array('@fieldname' => $element[$subfield]['#title'])));
        }
        if (!is_integer($$subfield)) {
          form_error($element[$subfield], t("Integer values only for '@fieldname'", array('@fieldname' => $element[$subfield]['#title'])));
        }
      }
      if ($$subfield < 0) {
        form_error($element, t("Negative values not allowed for '@fieldname'", array('@fieldname' => $element[$subfield]['#name'])));
      }
    }
  }

  $float =  mcapi_amount_value($element);
  $temp = $element['#value'];
  $element['#value'] = $float;
  mcapi_validate_quantity($element, $form_state);
  $element['#value'] = $temp;

  if ($element['#required'] == TRUE && !$element['integer']['#value'] && empty($element['division']['#value'])) {
    form_error($element, t('Quantity cannot be zero'));
  }
  //CRITICAL write this value to form_state where it will be incorporated into the writable object
  //the form validation should check if this is > 0
  $form_state['values']['quantity'] = $float;
}


function mcapi_webforms_ajax_amount($form, &$form_state) {
  return $form['amount']['#children'];
}


function theme_mcapi_amount($variables) {
  $element = &$variables['amount'];
  if (!empty($element['#cid'])) {
    $parts[] = drupal_render($element['integer']);
    if (isset($element['division'])) {
      $parts[] = drupal_render($element['division']);
    }
    $output = mcapi_format_money($parts, $element['#cid']);
  }
  else {
    $output = drupal_render($element['integer']);
  }
  return '<span class="amount" id = "'. $element['#id'].'">'. $output .'</span>';
}


function mcapi_amount_value($element) {
  $integer = $element['integer']['#value'];
  $division = isset($element['division']['#value']) ? $element['division']['#value'] : 0;
  if ($division) return $integer + $division/100;
  return $integer;
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
  $transactions = transaction_controller('load', $form_state['#transactions']['xids']);
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
  //look for the webform object for the redirect
  $form_state['redirect'] = str_replace(
    '#xid',
    $form_state['#transaction']->xid,
    $form_state['#webform']->data['submission']['redirect']
  );
}

//ascertains whether the active path corresponds to this webform
function _is_this_webform($webform) {

  $parts = explode('/', current_path());
  if ($parts[2] = 'formedit') return TRUE;
  $map = explode('/', $webform->path);
  debug($parts);debug($map);
  foreach($map as $key => $part) {
    if (strpos($part, '%') === 0) $parts[$key] = $part;
  }
  return $parts == $map;
}