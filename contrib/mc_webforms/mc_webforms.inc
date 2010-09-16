<?php
// $Id$

/*
 * 3rd party form. This is for third parties to create transactions
 * It's pretty close to the api's default node form, so not too much code is required.
 */
function mc_3rdparty_form($form_state, $prefill = NULL) {
  if (!$form_state['storage']['step']) {
    $form_state['storage']['step'] = 1;
  }
  $values = isset($form_state['storage']['values']) ? $form_state['storage']['values'] : '' //this is for stage 1 errors and stage 2
    or $values = $form_state['post'] //for the AHAH in stage 1
    or $values = $prefill //passed from mc_webform_block or menu callback
    or $values = array();
  if (is_numeric($values)) {
    $values = array('cid' => $values); //this is from the menu callback
  }
  if ($form_state['storage']['step'] == 1){
    //get the default exchange form, with defaults
    $form = mcapi_form_exchange((object)$values);

    mc_build_webform($form, $values);
    
    //there are some circumstances the ajax is not needed, but I'm not going to make all the rules now
    //chooing the currency shows the ratings field, and the divisions, if the currency has them
    mc_ajaxify($form['cid'], 'mc_3rdparty_form');
    //in case the form is in a block, we need to specify the url for step 2
    $form['#action'] = url('exchange/3rdparty');
  }
  elseif ($form_state['storage']['step'] == 2){
    $form = exchange_step2_form($values);
    $form['#redirect'] = 'user';
  }
  return $form;
}


function mc_3rdparty_form_validate($form, &$form_state) {
  //the individual fields are already validated
  if ($form_state['storage']['step'] < 2) {
    //send the pseudo-node off for validation by the mcapi
    mcapi_validate((object)$form_state['values'], $form);
  }
}

//submission handler to control the form rebuilding and value storage
//returns TRUE if the form needs to be rebuilt
function mc_submit_multistep(&$form_state){
  if (arg(1) == 'ajax') return;
  //multistep processing
  if (!isset($form_state['storage']['values'])) {
    $form_state['storage']['values'] = array();
  }
  //increment the button, save the values, and return
  if ($form_state['clicked_button']['#id'] == 'edit-back') {
    $form_state['storage']['step']--;
    $form_state['storage']['values'] = $form_state['values'] + $form_state['storage']['values'];
    return TRUE;
  } elseif ($form_state['storage']['step'] < 2) {
    $form_state['storage']['step']++;
    $form_state['storage']['values'] = $form_state['values'] + $form_state['storage']['values'];
    return TRUE;
  }
}

//process the exchange form values create the exchange and goto
function mc_3rdparty_form_submit($form, &$form_state) {
  if (mc_submit_multistep($form_state)) return;
  $form_state['values'] += $form_state['storage']['values'];
  //clear the storage so that the form doesn't rebuild
  unset($form_state['storage']);
  $result = mc_webform_create($form_state['values'], t('Exchange registered: !link'));
  if ($result->success) drupal_goto('user');
}

function template_preprocess_mc_3rdparty_formspecial(&$vars) {
  foreach (element_children($vars['form']) as $widget) {
    if (isset($vars['form'][$widget]['#type']) && !in_array($vars['form'][$widget]['#type'], array('hidden', 'token'))) {
      $vars[$widget] = drupal_render($vars['form'][$widget]);
    }
  }
  $vars['buttons'] = drupal_render($vars['form']['buttons']);
  $vars['hidden'] = drupal_render($vars['form']);
}

function exchange_step2_form($values) {
  drupal_set_title(t('Is this the exchange you want to register?'));
  $form['#values'] = $values; //this can be used in theming
  //don't change the key values of these buttons!
  $form['buttons']['back'] = array(
    '#type' => 'submit',
    '#value' => t('Back'),
    '#weight' => -1
  );
  $form['buttons']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit'),
    '#weight' => -1
  );
  $form['#theme'] = 'mc_web_create_confirm_form';
  return $form;
}


function template_preprocess_mc_web_create_confirm_form($vars) {
  //we used to use the node teaser for these, using theme('node', $node, TRUE FALSE),
  //but at this point we don't have a node to offer
  $vars['teaser'] = theme('exchange_summary', (object)$vars['form']['#values']);
  $vars['buttons'] = drupal_render($vars['form']['buttons']);
  $vars['hidden'] = drupal_render($vars['form']);
}

//rather than use the node edit form, it's better to control it completely here
//in some cases (but not this module), it will be appropriate to use the same form for creating and editing.
//This module emphasies flexibility on the creation form, and then limits the number of fields which can be edited
//This function shows all the transaction data, but allows editing only of rating, quantity, signees
function mc_edit_form($form_state, $node) {

  $form = mcapi_form_exchange($node);
  mc_build_webform($form, (array)$node);
  $form['nid'] = array(
    '#type' => 'hidden',
    '#value' => $node->nid
  );

  $form['#prefix'] = t("If you can't edit what you need to, delete this exchange and make a new one");
  //it's' too much trouble right now to sort out the rules which participants can be safely edited,
  //especially considering they may have been emailed asking for their signatures.

  if (user_access('edit all exchanges')) {
    $form['buttons']['erase'] = array(
      '#type' => 'submit',
      '#value' => t('Erase'),
      '#submit' => array('node_form_delete_submit')
    );
  }

  $form['#validate'] = array('mc_3rdparty_form_validate');
  //$form['#redirect'] = 'node/'.$node->nid; //this breaks the confirm_form button
  //change the text on the submit button
  $form['buttons']['submit']['#value'] = t('Save');
  return $form;
}


function mc_edit_form_submit($form, $form_state) {
  //it is only possible to have edited certain fields.
  $node = node_load($form_state['values']['nid']);
  $node->quantity = $form_state['values']['quantity'];
  $node->rating = $form_state['values']['rating'];
  $node->title = $form_state['values']['title'];
  $node->payee_uid =  $form_state['values']['payee_uid'];
  $node->payer_uid =  $form_state['values']['payer_uid'];
  //unsigned cannot be affected in this submit handler
  node_save($node);
  drupal_goto('node/'.$node->nid);
}

function mc_webform_settings() {
  $form['trader_selection'] = array(
    '#type' => 'fieldset',
    '#title' => t('Trader Selection'),
    '#description' => t("Users often need to select other users in forms, but sometimes a dropdownlist of usernames ordered by uid isn't the most usable solution"),
    '#weight' => -3
  );
  $form['trader_selection']['mc_trader_autocomplete'] = array(
    '#type' => 'checkbox',
    '#title' => t('Autocomplete trader names in transaction form'),
    '#default_value' => variable_get('mc_trader_autocomplete', TRUE),
    '#description' => t('Reccommended above about 150 users. If not enabled, will use selection widget below.'),
    '#weight' => 1,
  );
  $form['trader_selection']['mc_trader_selector'] = array(
    '#type' => 'textfield',
    '#title' => t('Widget for selecting users'),
    '#default_value' => variable_get('mc_trader_selector', '%uid | %theme_username'),
    '#weight' => 2,
    '#description' => t("Leave blank for autocomplete (reccommended above about 150 users).") .' '.
      t('Otherwise, specify how each row of the dropdown box should be formatted.') .' '.
      t('Use the following tokens: %uid, %username, %email, %theme_username') .' '.
      t('Dropdown list will be ordered alphabetically.')
  );
  $form['mc_description_min_words'] = array(
    '#type' => 'textfield',
    '#title' => t('Minimum number of words to describe an exchange'),
    '#default_value' => variable_get('mc_description_min_words', 2),
    '#size' => 2,
    '#maxlength' => 3,
    '#weight' => -2,
    '#description' => t("Classification can be improved if members take a few seconds to describe their exchanges in more detail.") .' '.
      t("It will also make the exchange easier to categorise, and easier for the participants to remember.") . ' '. t('Zero means no minimum. Suggested value is 4.')
  );
  $form['mc_webform_special_theme'] = array(
    '#type' => 'radios',
    '#title' => t('Special theming mode'),
    '#default_value' => variable_get('mc_webform_special_theme', TRUE),
    '#required' => TRUE,
    '#options' => array(
      FALSE => t("Start with the Drupal defaults, and theme forms as normal"),
      TRUE => t('Use the special theming features.')
    ),
    '#description' => t('Transaction forms need to be exactly right. Special theming mode offers a ready-made transaction form and some form-widget theme overrides which avoid !theme_form_element ',
        array('!theme_form_element' => l('theme_form_element()', 'http://api.drupal.org/api/function/theme_form_element/6'))) .' '.
      t('Then, using the provided tpl.php, you can arrange things more exactly as you want.') .' ',
  );
  return system_settings_form($form);
}

function mc_webform_sign_all_confirm_form($form_state, $nid) {
  $form = array();
  $form['#redirect'] = 'node/'. $nid;
  $form['nid'] = array(
    '#type' => 'hidden',
    '#value' => $nid
  );
  return confirm_form(
    $form,
    t('Are you sure you want to sign this exchange on behalf of all the remaining signatories?'),
    'user',
    '',
    t('Sign'), t('Cancel'),
    'sign'
  );
}


//common building tasks for all forms in this module
function mc_build_webform(&$form, $values = NULL) {
  //need to put the 'please choose' on the beginning of the array without buggering up all the array keys, one of which may be '0'
  if ($form['rating']['#type'] == 'select') {
    foreach ($form['rating']['#options'] as $key => $description) {
      $options[$key] = $description;
    }
    $form['rating']['#options'] = $options;

    if (!isset($values['rating']) && !isset($form['rating']['#default_value'])) {
      $form['rating']['#default_value'] = 'null';
    }
  }
  else unset($form['rating']);

  $form['exchange_type'] = array(
    '#type' => 'hidden',
    '#value' => '3rdparty',
  );
  //this can't be in the api because the node-form tries to add and screws up.
  $form['type'] = array(
    '#type' => 'hidden',
    '#value' => 'exchange',
  );
  if (isset($values['is_block']) && $values['is_block'] && arg(0) != 'exchange') {
    //this controls the special theming between pages and and ajax calls. see mc_specialtheme
    $form['is_block'] = array(
      '#type' => 'hidden',
      '#value' => $values['is_block'],
    );
  }
  //if unaltered, this will go through as zero, or EXCHANGE_STATE_FINISHED
  $form['state']['#type'] = 'hidden';

  //see settings page. set up for special theming with normal or block callback.
  if (variable_get('mc_webform_special_theme', TRUE)) {
    $form['#after_build'][] = 'mc_specialtheme';
  }
  //this is a proxy field widget. The validation actually puts a float, quantity, into $form_state
  $form['mc_quantity'] = array(
    //this fieldtype is defined by hook_form_elements.
    '#type' => 'mc_quantity',
    //putting validate function here means it can be added to - in form elements it would be overwritten
    '#element_validate' => array('validate_mc_quantity_field'),
  ) + $form['quantity'];
  //if ($values['mc_quantity']) $form['mc_quantity']['#default_value'] = $values['mc_quantity'];
  unset($form['quantity']);
  
  //more proxy fields
  $form['payer'] = array(
    '#type' => 'mc_trader',
    '#dest_field' => 'payer_uid',
    '#default_value' => isset($values['payer']) ? $values['payer'] : '' or
      isset($values['payer_uid']) ? mc_trader_field_getname($values['payer_uid']) : '',
  ) + $form['payer_uid'];
  unset($form['payer_uid']); //this is replaced in the mc_trader's validate function
  $form['payee'] = array(
    '#type' => 'mc_trader',
    '#dest_field' => 'payee_uid',
    '#default_value' => isset($values['payee']) ? $values['payee'] : '' or
      isset($values['payee_uid']) ? mc_trader_field_getname($values['payee_uid']) : '',
  ) + $form['payee_uid'];
  unset($form['payee_uid']);

  $form['title']['#element_validate'][] = 'mc_validate_webform_title';

  $form['buttons'] = array(
    'submit' => array(
      '#type' => 'submit',
      '#value' => t('Submit'),
    ),
    '#weight' => 30
  );
  $form['buttons']['submit']['#value'] = t('Next');
}


function refresh_trader_names() {
  if ($roles = user_roles(TRUE, 'exchange')) {
    $where = array('status = 1',  'u.uid > 0');
    if (!$roles[DRUPAL_AUTHENTICATED_RID]) {
      $where[] = 'rid in ('. implode(', ', array_keys($roles)) .')';
    }
    $results = db_query("SELECT u.uid
      FROM {users} u LEFT JOIN {users_roles} ur ON u.uid = ur.uid
      WHERE %s",
        array(':where' => implode(' AND ', $where))
    );

    $account = array();
    $pattern = variable_get('cc_trader_selector', '%uid | %theme_username');

    while ($account = user_load(db_result($results))) {
      $replacements = array(
        '%uid' => $account->uid,
        '%name' => $account->name,
        '%email' => $account->mail,
        '%theme_username' => strip_tags(theme('username', $account))
      );
      $names[$account->uid] = strtr($pattern, $replacements);
    }
    natcasesort($names);
    cache_set('cc_trader_list', $names);
  }
  else {
    drupal_set_message(
      t(
        "No roles have permission to '@trade'. Go to !link",
        array(
          '@trade' => t('exchange'),
          '!link' => l('admin/user/permissions', 'admin/user/permissions')
        ),
      'warning'
      )
    );
  }
}


//this function is built in to v7
function ajax_form_callback() {
  $form_state = array('storage' => NULL, 'submitted' => FALSE);
  $form_build_id = $_POST['form_build_id'];
  $form = form_get_cache($form_build_id, $form_state);
  $args = $form['#parameters'];
  $form_id = array_shift($args);
  $form_state['post'] = $form['#post'] = $_POST;
  $form['#programmed'] = $form['#redirect'] = FALSE;
  return drupal_rebuild_form($form_id, $form_state, $args, $form_build_id);
}


//because each exchangetype has it's own edit form this implements a new HOOK
function mc_edit_forms($node) {
  $hook = 'exchange_type_info';
  foreach (module_implements($hook) as $module) {
    $function = $module .'_'. $hook;
    $types = $function ($node->exchange_type);
    $edit_callback = &$types[$node->exchange_type]['edit'];
    if (function_exists($edit_callback)) {
      return drupal_get_form($edit_callback, $node);
    }
  }
  module_load_include('pages.inc', 'node');
  return drupal_get_form($node->type .'_node_form', $node);
}
/*
 * Implementation of webforms hook, hook_exchange_type_info
 */

function mc_webforms_exchange_type_info() {
  return array(
    '3rdparty' => array(
      'title' => '3rd party',
      'edit' => 'mc_edit_form'
    )
  );
}


//shows a confirm form and/or completes the transaction
function mc_sign_form(&$form_state, $exchange) {
  $form = array();
  $form['#node'] = $exchange;
  $form['#signer'] = arg(3);
  $form['#prefix'] = theme('node', $exchange, FALSE, TRUE);
  $form['#redirect'] = 'node/'. $exchange->nid;
  return confirm_form(
    $form,
    t('Are you sure you want to sign this exchange?'),
    'user',
    '',
    t('Sign'), t('Cancel'),
    'sign'
  );
}

//note that this does not count as a node update.
//that's why it calls nodeapi update.
function mc_sign_form_submit($form, &$form_state) {
  $nid = $form['#node']->nid;
  $result = db_query("SELECT uid FROM {mc_unsigned} WHERE nid = %d", array(':nid' => $nid));
  while ($uid = db_result($result)) {
    if ($uid == $form['#signer']) {
      db_query("DELETE FROM {mc_unsigned} WHERE nid = %d AND uid = %d", array(':nid' => $nid, ':uid' => $form['#signer']));
      drupal_set_message(t('!signer signed the exchange', array('!signer' => theme('username', user_load($form['#signer'])))));
    }
    else {
      $unfinished = TRUE;
    }
  }
  if (!$unfinished) {
    $form['#node']->state = EXCHANGE_STATE_FINISHED;
    node_save($form['#node']);
    drupal_set_message(t('The exchange is now finished'));
  }
}

function theme_select_stripped($element) {
  $select = '';
  $size = $element['#size'] ? ' size="'. $element['#size'] .'"' : '';
  _form_set_class($element, array('form-select'));
  $multiple = $element['#multiple'];
  return '<select name="'. $element['#name'] .''. ($multiple ? '[]' : '') .'"'. ($multiple ? ' multiple="multiple" ' : '') . drupal_attributes($element['#attributes']) .' id="'. $element['#id'] .'" '. $size .'>'. form_select_options($element) .'</select>';
  //return theme('form_element', $element, '<select name="'. $element['#name'] .''. ($multiple ? '[]' : '') .'"'. ($multiple ? ' multiple="multiple" ' : '') . drupal_attributes($element['#attributes']) .' id="'. $element['#id'] .'" '. $size .'>'. form_select_options($element) .'</select>'
}
function theme_textfield_stripped($element) {
  $size = empty($element['#size']) ? '' : ' size="'. $element['#size'] .'"';
  $maxlength = empty($element['#maxlength']) ? '' : ' maxlength="'. $element['#maxlength'] .'"';
  $class = array('form-text stripped');
  $extra = '';
  $output = '';

  if ($element['#autocomplete_path'] && menu_valid_path(array('link_path' => $element['#autocomplete_path']))) {
    drupal_add_js('misc/autocomplete.js');
    $class[] = 'form-autocomplete';
    $extra =  '<input class="autocomplete" type="hidden" id="'. $element['#id'] .'-autocomplete" value="'. check_url(url($element['#autocomplete_path'], array('absolute' => TRUE))) .'" disabled="disabled" />';
  }
  _form_set_class($element, $class);

  if (isset($element['#field_prefix'])) {
    $output .= '<span class="field-prefix">'. $element['#field_prefix'] .'</span> ';
  }

  $output .= '<input type="text"'. $maxlength .' name="'. $element['#name'] .'" id="'. $element['#id'] .'"'. $size .' value="'. check_plain($element['#value']) .'"'. drupal_attributes($element['#attributes']) .' />';

  if (isset($element['#field_suffix'])) {
    $output .= ' <span class="field-suffix">'. $element['#field_suffix'] .'</span>';
  }
  return $output . $extra;
}

//we need this theme function because otherwise we don't know which widget to use
function theme_mc_trader($element) {
  if (!isset($elements['#printed'])) {
    return theme($element['#element_type'], $element);
  }
}
function theme_mc_trader_stripped($element) {
  if (!isset($elements['#printed'])) {
    return theme($element['#element_type'] . '_stripped', $element);
  }
}
function theme_mc_quantity($element) {
  $currname = isset($element['#single_currency_name']) ? $element['#single_currency_name'] : '';
  $naked = '<div class="container-inline">'. $element['#children'] . $currname .'</div>';
  //assumes the children have been themed with theme_form_item().
  //Under no circumstances to we want to view the labels for the child fields
  foreach (element_children($element) as $child) {
    $classes[] = '#'. $element[$child]['#id'] .'-wrapper label';
  }
  $css = "<style>".implode(',', $classes)."{display:none;}</style>\n";
  //it's not supposed to be this way, but overrides of this theme wouldn't work, looks to me like drupal render is badly thought out
  //so this theme function checks a variable from mc_webforms. the alternative is to override this in mc_webforms with phptemplate_mc_quality

  if (variable_get('mc_webform_special_theme', FALSE)) {
    return $css.$naked;
  }
  else return theme('form_element', $element, $css.$naked);
}

//apply the 'special' theme functions and templates to any form (this is a form #after_build function)
function mc_specialtheme($form, $form_state) {
  //set the form #theme to it's 'special' template; the edit form mc_edit_form borrows the thirdparty form
  $baseform_id = $form['form_id']['#value'] == 'mc_edit_form' ? 'mc_thirdparty_form' : $form['form_id']['#value'];
  $form['#theme'] = $baseform_id.'special';
  if (isset($form_state['values']['is_block'])) {
    $form['#theme'] .= '_block';
  }
  //make special the form elements
  foreach ($form as $key => $element) {
    if (isset($element['#type']) && in_array($element['#type'], array('textfield', 'select', 'mc_trader'))) {
      $form[$key]['#theme'] = $element['#type'].'_stripped';
    }
  }
  return $form;
}
//other args are the fieldnames
function mc_ajaxify(&$element, $form_id) {
  if ($element['#type'] == 'hidden') return;
  $element['#ahah'] = array(
    'path' => 'exchange/ajax',
    'wrapper' => str_replace(array('][', '_', ' '), '-', $form_id), //see form_clean_id()
    'progress' => array('type' => 'throbber')
  );
}

//the $element has been validated, now we set the payer and payee in $form_state
function mc_trader_validate($element, &$form_state) {
  if (!$element['#value']) {
    form_error($element, t('There must be two participants in any exchange.'));
  }
  $uid = _mc_check_trader($element['#value'], $element); //this delievers it's own error messages
  
  $form_state['values'][$element['#dest_field']] = $uid;
}

//is this being used?
function mc_trader_field_getname($uid){
  $account = user_load($uid);
  return $account->name;
}


//ajax menu callback
function mc_ajax_webform($block = FALSE) {
  module_load_include('inc', 'mc_webforms');
  $form = ajax_form_callback();
  //to prevent rendering the form tag itself, since we are replacing only the contents of the form tag
  $form['#printed'] = TRUE;
  //this is probably redundant
  if ($block)$form['#theme'] = 'mc_web_create_form_block';
  //clear the form errors and messages before re-rendering the form
  form_set_error(NULL, NULL, TRUE);
  drupal_get_messages(NULL, TRUE);
  $rendered = drupal_render($form);
  drupal_json($rendered);
}
