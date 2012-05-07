<?php

/*
 * In this file we build the form, using the object cached in ctools
 */
module_load_include('inc', 'mcapi');

/*
 * main form builder, for this module, using the transaction_form as a base
 * build_info args are 0=> transaction, 1 => mcapi_form
 */
function _mcapi_forms_form_mcapi_forms_form_alter(&$form, &$form_state, $form_id) {
  $mcapi_form = &$form_state['build_info']['args'][1];
  $transaction = isset($form_state['transaction']) ? $form_state['transaction'] : (
    $form_state['build_info']['args'][0] ? $form_state['build_info']['args'][0] :
    new_transaction_presets($mcapi_form->data)
  );

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
        if (!empty($mcapi_form->data[$instance['field_name']]['preset'])) {
          $transaction->{$instance['field_name']} = $mcapi_form->data[$instance['field_name']]['preset'];
        }
      }
    }
  }
  $form['#step'] = $form_state['step'];

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
    foreach (array('payer', 'payee') as $participant) {
      $form[$participant]['#type'] = strtok($mcapi_form->data[$participant]['args'], ':');
      $form[$participant]['#args'] = array(strtok(':'));
      //this could be put in the config
      $form[$participant]['#me'] = TRUE;
      $form[$participant]['#default_value'] = $transaction->$participant;
    }
  }
  else {
    //I-thou exchanges
    //this section cannot be used to edit existing transactions
    $form['secondperson'] = array(
      '#default_value' => isset($form_state['values']['secondperson']) ? $form_state['values']['secondperson'] : $transaction->secondperson
    ) + $form['payee'];
    $form['secondperson']['#type'] = strtok($mcapi_form->data['secondperson']['args'], ':');
    $form['secondperson']['#args'] = array(strtok(':'));
    $form['secondperson']['#me'] = FALSE;
    $form['secondperson']['#required'] = TRUE;

    $form['direction'] = array(
      '#type' => $mcapi_form->data['direction']['widget'] ? $mcapi_form->data['direction']['widget'] : 'select',
      '#options' => array(
        'incoming' => $mcapi_form->data['direction']['incoming'],
        'outgoing' => $mcapi_form->data['direction']['outgoing']
      ),
      //if we are loading the page from blank, or rebuilding the form, we set the default values here,
      '#default_value' => $transaction->direction,
      '#required' => TRUE
    );
    //if we are loading the page from blank, or rebuilding the form, we set the default values here
    //however if we are editing an existing transaction,
    //we have to show the transaction owner and derive the direction and partner from the payer and payee
    if (!empty($transaction->xid)) {
      if ($transaction->creator == $transaction->payer) {
        $form['secondperson']['#default_value'] = $transaction->payee;
        $form['direction']['#default_value'] = 'outgoing';
      }
      else{
        $form['secondperson']['#default_value'] = $transaction->payer;
        $form['direction']['#default_value'] = 'incoming';
      }
    }
    unset($form['payer'], $form['payee']);
    //this validation function must run BEFORE the main one in the API, because its add payer and payee, which are required for normal validation
    array_unshift($form['#validate'], 'mcapi_forms_validate_secondperson');
  }

  //we now modify the transaction form fields according to the form settings for each field
  foreach (array_keys(field_info_instances('transaction', 'transaction')) as $fieldname) {
    if (isset($transaction->$fieldname)) {
      $default = mcapi_recursive_default_value($transaction->$fieldname);
    }
    $form[$fieldname] = array_replace_recursive($form[$fieldname], $default);
  }
  //specially for the worth field, remove any $items which are not in the default $transaction object
  //see mcapi_form_validate_worth_preset() for more
  foreach (element_children($form['worth']['und']) as $delta) {
    if (empty($transaction->worth['und'][$delta])) unset($form['worth']['und'][$delta]);
  }

  //save button
  if (strpos($mcapi_form->data['submission']['button'], '/')) {
    $form['buttons']['submit']['#type'] = 'image_button';
    $form['buttons']['submit']['#src'] = $mcapi_form->data['submission']['button'];
  }
  else {
    $form['buttons']['submit']['#value'] = $mcapi_form->data['submission']['button'];
  }
  //some adjustements for the confirmation step
  if ($form['#step'] > MCAPIFORM_STEP_ENTER && !empty($form_state['values'])) {
    mcapi_hide_tabs();
    $form['buttons']['back'] = array(
      '#type' => 'submit',
      '#value' => t('Back'),
    );
    //bypass the default transaction validate function, transaction_form_validate which rebuilds the transaction entity from form_state
    //not considering that in step 2 of the web form  the transaction elements are in $form_state['storage] not $form_state values
    $form['#validate'] = array();
  }

  //this may help with the validation and submission
  $form['#contextual_links']['mcapi_forms'] = array('admin/accounting/forms/list', array($mcapi_form->name));
  //some special form properties tell the theme callback which template to use.
  $form['#template'] = mcapi_form_template($mcapi_form, $form['#step'])->template;
  $form['#theme'] = 'mcapi_form';

  $form['#validate'][] = 'mcapi_forms_validate';
  array_unshift($form['#submit'], 'mcapi_forms_form_presubmit');
  array_push($form['#submit'], 'mcapi_forms_form_postsubmit');//can't remember why use array_push and not just [] operator
}


function new_transaction_presets($mcapi_formdata) {
  $transaction = new_transaction();
  //this transaction is used to store defaults for the form building, so it has pseudo properties which aren't saved
  $fieldnames = $mcapi_formdata['perspective'] == 1 ? array('secondperson', 'direction') : array('payer', 'payee');
  $fieldnames = array_merge($fieldnames, array_keys(field_info_instances('transaction', 'transaction')));
  foreach($fieldnames as $fieldname) {
    if (isset($mcapi_formdata[$fieldname]['preset'])) {
      $transaction->$fieldname = $mcapi_formdata[$fieldname]['preset'];
    }
  }
  return $transaction;
}

/*
 * utility function
 * Takes an array and converts every deepest value into an array('#default_value' => $value);
 */
function mcapi_recursive_default_value($value) {
  if (is_array($value)) {
    $result = array();
    foreach ($value as $key => $val) {
      $result[$key] = mcapi_recursive_default_value($val);
    }
    return $result;
  }
  return array('#default_value' => $value);
}

/*
 * $variables consists of the usual suspects plus
 * $template
 * $mcapi_form_data
 */
function theme_mcapi_form($variables) {
  $form = &$variables['form'];
  $buttons = drupal_render($form['buttons']);
  mcapi_unset_recursive($form, '#theme_wrappers');
  //render the visible stuff first
  $output = token_replace($form['#template'], $form, array('sanitize' => FALSE));
  return $output .= $buttons;
}
/*
 * Validation callback
 * set the payer and payee from the 2ndpeson and direction
 * BEFORE $form_state['transaction'] is created
 */
function mcapi_forms_validate_secondperson($form, &$form_state) {
  $values = &$form_state['values'];
  if ($values['direction'] == 'incoming') {
    $values['payer'] = $values['secondperson'];
    $values['payee'] = $GLOBALS['user']->uid;
  }
  elseif ($values['direction'] == 'outgoing') {
    $values['payer'] = $GLOBALS['user']->uid;
    $values['payee'] = $values['secondperson'];
  }
}

/*
 * form validation callback
 * handles the steps
 */
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
 * copies anything in $form_state[storage] back to form_state[values]
 */
function mcapi_forms_form_presubmit($form, &$form_state) {
  $form_state['values'] = $form_state['storage'];
}
/*
 * Last submit callback for default transaction form
 * this has to be after the transaction id is created and before the end of form processing
 */
function mcapi_forms_form_postsubmit($form, &$form_state) {
  $form_state['redirect'] = str_replace(
    array('%transaction', '%user'),
    array($form_state['transaction']->xid, $GLOBALS['user']->uid),
    $form_state['build_info']['args'][1]->data['submission']['redirect']
  );

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

/*
 * pre php5.3
 */
if (!function_exists('array_replace_recursive')) {
  function array_replace_recursive($array, $array1) {
    // handle the arguments, merge one by one
    $args = func_get_args();
    $array = $args[0];
    if (!is_array($array)) {
      return $array;
    }
    for ($i = 1; $i < count($args); $i++) {
      if (is_array($args[$i])) {
        $array = recurse($array, $args[$i]);
      }
    }
    return $array;
  }
  function recurse($array, $array1) {
    foreach ($array1 as $key => $value) {
      // create new key in $array, if it is empty or not an array
      if (!isset($array[$key]) || (isset($array[$key]) && !is_array($array[$key]))) {
        $array[$key] = array();
      }
      // overwrite the value in the base array
      if (is_array($value)) {
        $value = recurse($array[$key], $value);
      }
      $array[$key] = $value;
    }
    return $array;
  }
}