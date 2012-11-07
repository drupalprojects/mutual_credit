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
  $args = &$form_state['build_info']['args'];
  $mcapi_form = mcapi_forms_load($args[MCAPIFORM_BUILD_INFO_ARG])->data;

  $transaction = isset($form_state['storage']) ? (object)$form_state['storage'] : (
    $args[TRANSACTION_BUILD_INFO_ARG] ? $args[TRANSACTION_BUILD_INFO_ARG] :
    new_transaction_presets($mcapi_form)
  );

  //Because some forms may even bypass step one, and start with the confirmation step
  if (empty($form_state['step'])) {
    if ($mcapi_form['experience']['title']) {
      $form_state['storage'] = array();
      $form_state['step'] = 1;
      //populate the default transaction object with values from the form settings
      foreach (field_info_instances('transaction', 'transaction') as $instance) {
        //The transaction might contain new fields not known about by the saved form
        if (!empty($mcapi_form[$instance['field_name']]['preset'])) {
          $transaction->{$instance['field_name']} = $mcapi_form[$instance['field_name']]['preset'];
        }
      }
    }
    else {//jump to step 2 if there's no title for step 1
      $form_state['step'] = 2;
    }
  }
  //we need to store the step value in $form so that the theme system knows what to do
  $form['#step'] = $form_state['step'];
  $form['#theme'] = 'mcapi_form';
  //this might not be the way to build a 2 step form.
  //All the validation callbacks need to check the step because all the important fields are in step one only.
  if ($form['#step'] == 1) {
    $form['#template'] = mcapi_locale($mcapi_form['experience']['template'], 'template');
    $form['creator']['#type'] = 'hidden';
    $form['state']['#type'] = 'hidden';
    $form['state']['#value'] = TRANSACTION_STATE_FINISHED;
    $form['type']['#type'] = 'value';
    $form['type']['#value'] = $mcapi_form['info']['name'];

     //participants
    if ($mcapi_form['perspective'] == 3) {
      foreach (array('payer', 'payee') as $participant) {
        //specifies which user_chooser widget
        $form[$participant]['#type'] = strtok($mcapi_form[$participant]['args'], ':');
        $form[$participant]['#args'] = array(strtok(':'));
        $form[$participant]['#default_value'] = $transaction->$participant;
        $form[$participant]['#allow_me'] = TRUE;//this could be put in the config
      }
    }
    else {
      //I-thou exchanges
      $form['secondperson'] = array(
        '#default_value' => empty($transaction->secondperson) ? '' : $transaction->secondperson
      ) + $form['payee'];
      //specifies which user_chooser widget
      $form['secondperson']['#type'] = strtok($mcapi_form['secondperson']['args'], ':');
      $form['secondperson']['#args'] = array(strtok(':'));
      $form['secondperson']['#required'] = TRUE;

      $form['secondperson']['#allow_me'] = FALSE;//this could be put in the config
      $type = $mcapi_form['direction']['widget'] ? $mcapi_form['direction']['widget'] : 'select';
      $options = $type == 'select' ? array(t('Please choose...')) : array();
      $form['direction'] = array(
        '#type' => $type,
        '#options' => $options + array(
          'incoming' => $mcapi_form['direction']['incoming'],
          'outgoing' => $mcapi_form['direction']['outgoing']
        ),
        //if we are loading the page from blank, or rebuilding the form, we set the default values here,
        '#default_value' =>  $mcapi_form['direction']['preset'],
        '#required' => TRUE
      );
      if (empty($form['direction']['#default_value'])) {
        $form['direction']['#default_value'] = $GLOBALS['user']->uid == $transaction->payee ? 'incoming' : 'outgoing';
      }
      //if we are loading the page from blank, or rebuilding the form, we set the default values here
      if ($mcapi_form['direction']['preset']) {
        $form['direction']['#default_value'] = $mcapi_form['direction']['preset'];
      }
      if ($mcapi_form['secondperson']['preset']) {
        $form['secondperson']['#default_value'] = $mcapi_form['secondperson']['preset'];
      }
      unset($form['payer'], $form['payee']);
      //this validation function must run BEFORE the main one in the API, because its add payer and payee, which are required for normal validation
      array_unshift($form['#validate'], 'mcapi_forms_validate_secondperson');
    }
    //we now modify the transaction form fields according to the form settings for each field
    foreach (array_keys(field_info_instances('transaction', 'transaction')) as $fieldname) {
      $default = array();
      if (isset($transaction->$fieldname)) {
        $default = mcapi_recursive_default_value($transaction->$fieldname);
      }
      $form[$fieldname] = array_replace_recursive($form[$fieldname], $default);
    }
    //if the cardinality is 1 and a default language is given, then confine the widget to that one language
    if ($form['worth'][LANGUAGE_NONE]['#cardinality'] == 1 && $transaction->worth[LANGUAGE_NONE][0]['currcode'] ) {
      $form['worth'][LANGUAGE_NONE][0]['#restrict_currcodes'] = array($transaction->worth[LANGUAGE_NONE][0]['currcode']);
    }
    //specially for the worth field: remove any $items whose values are NULL in the default transaction object
    //and change any zero values to empty strings in the main_quant
    //should this be part of the mcapi.module?
    //see mcapi_form_validate_worth_preset() for more
    foreach (element_children($form['worth']['und']) as $delta) {
      if ($transaction->worth['und'][$delta]['main_quant'] == '' && empty($transaction->worth['und'][$delta]['division_quant'])) {
        //this currency wasn't included on this form
        unset($form['worth']['und'][$delta]);
      }
      elseif ($transaction->worth['und'][$delta]['main_quant'] == 0) {
        $form['worth']['und'][$delta]['main_quant']['#default_value'] = '';
      }
    }
  }
  elseif ($form['#step'] == 2) {
    drupal_set_title(mcapi_locale($mcapi_form['experience']['sure_title'], 'sure_title'));
    foreach(element_children($form) as $fieldname) {
      if (in_array($fieldname, array('buttons', 'form_id', 'form_build_id', 'form_token'))) continue;
      unset($form[$fieldname]);
    }
    //put the back button only for last step if there are values from step 1
    if (!empty($form_state['values'])) {//coming from step 1
      $form['#preview'] = transaction_view(
        $form_state['transactions'],
        mcapi_locale($mcapi_form['experience']['sure_format'], 'sure_format'),
        mcapi_locale(@$mcapi_form['experience']['sure_template'], 'sure_template')
      );
      mcapi_hide_tabs();
      $form['actions']['back'] = array(
        '#type' => 'submit',
        '#value' => t('Back'),
      );
      array_unshift($form['#submit'], 'mcapi_forms_form_presubmit');
    }
    else {//landing directly on step 2
      $form['#preview'] = transaction_view(array($transaction->xid => $transaction), $mcapi_form['experience']['sure_format']);
    }

    //the transaction properties were validated in step 1
    //bypass the default transaction validate function, transaction_form_validate which expects stuff in form_state['values]
    unset($form['#validate']);
  }
  //if we just put the strip at the top level of each element,
  //then it will get lost as the element-language-items go to theming
  foreach (element_children($form) as $fieldname) {
    if (isset($mcapi_form[$fieldname]['strip'])) {
      if(isset($form[$fieldname]['#title'])) {//normal fields
        $form[$fieldname]['#strip'] = $mcapi_form[$fieldname]['strip'];
      }
      else {//fieldAPI
        //I think we can get away with assuming that all transactions are LANGUAGE_NONE
        $form[$fieldname][LANGUAGE_NONE]['#strip'] = $mcapi_form[$fieldname]['strip'];
      }
    }
  }
  //save button
  $form['buttons']['#type'] = 'actions';
  if (strpos($mcapi_form['submission']['button'], '/')) {
    $form['buttons']['submit']['#type'] = 'image_button';
    $form['buttons']['submit']['#src'] = $mcapi_form['submission']['button'];
  }
  else {
    $form['buttons']['submit']['#value'] = $mcapi_form['submission']['button'];
  }
  array_push($form['#submit'], 'mcapi_forms_form_postsubmit');//can't remember why use array_push and not just [] operator
  $form['#validate'][] = 'mcapi_forms_validate';

  //themeing: we want to do all we can to allow the form elements to run together,
  //similar to stripping the theme_wrapper we want everything to display:inline
  drupal_add_css(
    '#mcapi-forms-form div,
    #mcapi-forms-form div.form-type-checkboxes .form-type-checkbox label,
    #mcapi-forms-form .form-type-radios .form-type-radio label {display:inline}
    #mcapi-forms-form .form-type-checkboxes label,
    #mcapi-forms-form .form-type-radios label{display:none;}',
    array('type' => 'inline')
  );

  //this may help with the validation and submission
  //however it doesn't work because we need to rebuild the menu after editing the form
  //contextual links send the user back to this page,
  //but we are doing the rebuild as the form editing form returns to the admin/accounting/forms page
  //see list_table_header() in mcapi_forms_ui.class.php
//  $form['#contextual_links']['mcapi_forms'] = array('admin/accounting/forms/list', array($mcapi_form['info']['name']));

}

//create a transaction with all the defaults from the mcapi_form
//this is not a real transaction, but just used to store the defaults
//So it has fields either payer and payee OR direction and secondperson
//plus the fieldAPI fields
function new_transaction_presets($mcapi_formdata) {
  $fieldnames = array('payer', 'payee');
  $transaction = entity_metadata_create_transaction();
  //this transaction is used to store defaults for the form building,
  //so it may have pseudo-properties secondperson and direction
  if ($mcapi_formdata['perspective'] == 1) {
    unset($transaction->payer, $transaction->payee);
    $fieldnames[] = 'direction';
    $fieldnames[] = 'secondperson';
  }
  //hmmm the transaction type is not covered here - its assumed to derive from the formname
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
  //render the visible stuff first

  if ($form['#step'] == 1) {
    foreach (element_children($form) as $fieldname) {
      if (@$form[$fieldname]['#type'] != 'container') {//that means it's NOT field api field
        if (isset($form[$fieldname]['#strip']) && $form[$fieldname]['#strip']) {
          unset($form[$fieldname]['#theme_wrappers']);
        }
      }
      else {
        if (!empty($form[$fieldname][LANGUAGE_NONE]['#strip'])) {
          mcapi_unset_recursive($form[$fieldname][LANGUAGE_NONE], '#theme_wrappers');//this gets rid of one css wrapper
          mcapi_unset_recursive($form[$fieldname][LANGUAGE_NONE], '#title');
          mcapi_unset_recursive($form[$fieldname][LANGUAGE_NONE], '#description');
        }
      }
    }
    return token_replace(
      $form['#template'],
      $form,
      array('sanitize' => FALSE)
    ) . '<br />'.$buttons;
  }
  elseif ($form['#step'] == 2) {
    $preview = drupal_render($form['#preview']);
    return drupal_render_children($form) . $buttons . $preview;
  }
}

/*
 * Validation callback
 * set the payer and payee from the 2ndpeson and direction
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
  if (!empty($form_state['clicked_button']['#submit']) && in_array('mcapi_transaction_undo_confirm_form', $form_state['clicked_button']['#submit'])) {
    return;
  }

  $form_state['rebuild'] = FALSE;
  //go back if that button was pressed
  if (isset($form_state['values']['back']) && $form_state['values']['op'] == $form_state['values']['back']) {
    $form_state['rebuild'] = TRUE;
    $form_state['step']--;
    $form_state['values'] = $form_state['storage'];
    unset($form_state['transactions']);
  }
  //if step 2 template is available add a step and rebuild the form
  elseif (strlen($form_state['build_info']['args'][MCAPIFORM_BUILD_INFO_ARG]->data['experience']['sure_title'])) {
    if (!form_get_errors() && $form['#step'] == 1) {
      $form_state['storage'] = $form_state['values'] + $form_state['storage'];
      $form_state['rebuild'] = TRUE;
      $form_state['step']++;
    }
  }
}

/*
 * First submit callback for default transaction form
 * copies anything in $form_state[storage] back to form_state[values]
 * the $form_state['values'] is step 2 were only needed to render the tokens
 * the actual transaction values were in $form_state['storage']
 */
function mcapi_forms_form_presubmit($form, &$form_state) {
  $form_state['values'] = $form_state['storage'];
}

/*
 * Last submit callback for default transaction form
 * this has to be after the transaction id is created and before the end of form processing
 */
function mcapi_forms_form_postsubmit($form, &$form_state) {
  $submission = &$form_state['build_info']['args'][MCAPIFORM_BUILD_INFO_ARG]->data['submission'];
  if ($submission['feedback']) {
    //this could be more sophisticated by using tokens and transaction_load_last()
    drupal_set_message($submission['feedback']);
  }
  $redirect = str_replace('%user', $GLOBALS['user']->uid, $submission['redirect']);
  if (empty($redirect)) {
    $redirect = 'transaction/' . $form_state['values']['serial'];
  }
  drupal_goto($redirect);
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

function mcapi_locale(&$string, $key) {
  global $language;
  if ($language->language == 'en' || !function_exists('locale')) return $string;
  return locale($string, '', $language->language);
}