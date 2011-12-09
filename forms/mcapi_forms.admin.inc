<?php

module_load_include('inc', 'mcapi_forms');

/*
 * This is the form-builder form
 * For every field in the transaction form, this form allows it to be configured.
 * Its not geometrically perfect, but covers a lot of use cases without getting too complicated
 */
function _edit_mcapi_form(&$form, &$form_state) {
  form_load_include($form_state, 'inc', 'mcapi');//contains the validation functions for the default form elements
  $mcapi_form = &$form_state['item']->data;
  $form_id = $form_state['item']->name;

  //prepare variables for building the form
  $permissions = module_invoke_all('permission');
  $roles = user_roles(TRUE);
  foreach ($roles as $rid => $name){
    $access_options[t('Roles')]['mcapi_access_check_roles:'.$rid] = $name;
    $user_chooser_options[t('Roles')]['user_chooser_roles:'.$rid] = $name;
  }
  foreach ($permissions as $perm => $info){
    $access_options[t('Permissions')]['user_access:'.$perm] = strip_tags($info['title']);
    $user_chooser_options[t('Permissions')]['user_chooser_permissions:'.$perm] = strip_tags($info['title']);
  }
  $menus = db_select('menu_custom', 'm')->fields('m', array('menu_name', 'title'))->execute()->fetchAllKeyed();

  $form['#prefix'] = '<div id = "form-wrapper">';
  $form['#suffix'] = '</div>';
  //I think ctools is supposed to be handling this
  $form['info']['name']['#type'] = 'machine_name';
  $form['info']['name']['#machine_name']['exists'] = 'mcapi_forms_load';

  $perspectives = array(
    3 => t('3rd Party'),
    1 => t('1st Party')
  );
  //to cover up the fact that we can only reload the form once
  if (isset($form_state['values']['perspective'])) {
    //only include the selected option
    $form['perspective']['#options'] = array($form_state['values']['perspective'] => $perspectives[$form_state['values']['perspective']]);
    $form['perspective']['#description'] = 'Technical limitations prohibit this being flipped more than once. Reload the whole page to change it back';
  }

  $form['xfid'] = array(
    '#type' => 'hidden',
    '#value' => isset($form_state['item']->xfid) ? $form_state['item']->xfid : ''
  );
  $form['help'] = array(
    '#title' => t('Help'),
    '#description' => t('A quick description of the form, for the administrator'),
    '#type' => 'textfield',
    '#default_value' => isset($mcapi_form['help']) ? $mcapi_form['help'] : '',
    '#maxlength' => 255,
    '#weight' => 3
  );
  $form['architecture'] = array(
    '#title' => t('Architecture'),
    '#description' => t("Every form is a menu callback with its own access control. Don't forget currencies have their own access control as well."),
    '#type' => 'fieldset',
    '#collapsible' => TRUE,
    '#collapsed' => !empty($form_id),
    '#weight' => 3,
    'path' => array(
      '#title' => t('Path'),
      '#description' => t("Url of this form. Use optional literals '%transaction' and '%user'. See !url documentation for advanced usage", array('!url' => l('hook_menu','http://api.drupal.org/api/drupal/modules--system--system.api.php/function/hook_menu/7'))),
      '#type' => 'textfield',
      '#default_value' => isset($mcapi_form['architecture']['path']) ? $mcapi_form['architecture']['path'] : '',
      '#required' => TRUE,
      '#weight' => 2
    ),
    'access' =>array(
      '#title' => t('Access control'),
      '#description' => t("In addition to currency access control, and block access control, access to this form can be restricted."),
      '#type' => 'select',
      '#default_value' => isset($mcapi_form['architecture']['access']) ? $mcapi_form['architecture']['access'] : 'currency',
      '#options' => $access_options,
      '#element_validate' => array(),
    ),
    'menu_link' =>array(
      '#title' => t('Menu link location'),
      '#description' => t('Choose the menu, or menu item-type.') .' '. t("For more info, see 'type' info at !link", array('!link' => l('drupal.org', 'http://api.drupal.org/api/drupal/modules--system--system.api.php/function/hook_menu/7'))),
      '#type' => 'select',
      '#default_value' => isset($mcapi_form['architecture']['menu_link']) ? $mcapi_form['architecture']['menu_link'] : 'MENU_CALLBACK',
      '#options' => $menus+ array(
        MENU_CALLBACK => t('No menu item'),
        MENU_LOCAL_TASK => 'MENU_LOCAL_TASK',
        MENU_DEFAULT_LOCAL_TASK => 'MENU_DEFAULT_LOCAL_TASK',
        MENU_LOCAL_ACTION => 'MENU_LOCAL_ACTION'
      ),
      '#element_validate' => array(),
    ),
    'use_block' =>  array(
      '#title' => t('Manifest block'),
      '#description' => t('The block is named according the path.') .' '.
        t('Remember that blocks have their own caching and access control.'),
      '#type' => 'checkbox',
      '#default_value' => isset($mcapi_form['architecture']['use_block']) ? $mcapi_form['architecture']['use_block'] : '',
      '#element_validate' => array(),
    )
  );

  $states = mcapi_states();
  unset($states[TRANSACTION_STATE_INITIATED]);//as far as forms are concerned, this state is invisible
  //theme_checkboxes fails with array keys of zero, so we have to use the untranslated state names insteead
  foreach ($states as $state) {
    $outgoing_options[strtolower($state)] = t($state); //horrible to translate a variable, but must be done.
  }
  $incoming_options = $outgoing_options;
  unset($incoming_options['erased']);//erase transactions should never be editable //TODO modify currency permission fieldset to that effect
  unset($outgoing_options['unset']);
  $outgoing_options['null'] = '('.t('inherit').')';

  $form['perspective'] = array(
    '#title' => t('perspective'),
    '#description' => t('Whether this is completed by a 3rd party or a participant.'),
    '#type' => 'radios',
    '#options' => $perspectives,
    '#default_value' => isset($mcapi_form['perspective']) ? $mcapi_form['perspective'] : 3,
    '#weight' => 5,
    //this ajax only works once then the form #id increments to ctools-export-ui-edit-item-form--2
    '#ajax' => array(
      'callback' => 'mcapi_forms_ajax_settings',
      'wrapper' => 'form-wrapper'
    ),
  );

  //load the transaction form so we can use all the fields here to set the defaults
  $transaction_fields = transaction_form($form, $form_state, new_transaction());

  //this section of the form allows the admin to handle the individual fields of the transaction form.
  //the fields are handled here one in each tab, each field having some shared settings and some specific ones.
  $form['mcapi_form_fields'] = array(
    '#type' => 'vertical_tabs',
    '#weight' => 6,
    '#prefix' => '<style>.vertical-tabs-panes div.form-type-radio{display:inline;}</style>',
  );
  //the participants of the transaction are either payer/payee or 1stperson/2ndperson. This is called the 'perspective' of the form.
  $perspective = isset($form_state['values']['perspective']) ? $form_state['values']['perspective'] : $form['perspective']['#default_value'] ;
  if ($perspective == 3) {
    //special settings for payer and payee
    foreach (array('payer' => t('Payer ID'), 'payee' => t('Payee ID')) as $key => $name) {
      $form[$key] = _mcapi_form_shared($mcapi_form, $transaction_fields[$key], $key);
      $form[$key]['#widget'] = 'user_chooser';//this is the only option that will work right now
      $form[$key]['args'] = array(
        '#title' => t('Users to choose from'),
        '#description' => t('Configure this widget more at !link',
           array('!link' => l('admin/config/people/user_chooser', 'admin/config/people/user_chooser', array('attributes'=>array('target'=> '_blank')))
        )),
        '#type' => 'select',
        '#options' => $user_chooser_options,
        '#default_value' => isset($mcapi_form[$key]['args']) ? $mcapi_form[$key]['args'] : 'mcapi_access_check_roles:2'
      );
      $form[$key]['preset']['#description'] = t('Use [self] to preset with the logged in user');
      $form[$key]['multiple'] = array(
        '#title' => t('Multiple users'),
        '#description' => t('Allow multiple users and repeat the transaction for each of them'),
        '#type' => 'checkbox',
        '#default_value' => isset($mcapi_form[$key]['multiple']) ? $mcapi_form[$key]['multiple'] : FALSE,
      );
    }
  }
  else {//1st person form shows one field only, presuming the current user initiates the transaction
    $form['2ndperson'] = _mcapi_form_shared($mcapi_form, $transaction_fields['payer'], '2ndperson');
    $form['2ndperson']['#widget'] = 'user_chooser';//this is the only option that will work right now
    $form['2ndperson']['args'] = array(
      '#title' => t('Users to choose from'),
      '#description' => t('Configure this widget more at !link',
         array('!link' => l('admin/config/people/user_chooser', 'admin/config/people/user_chooser', array('attributes'=>array('target'=> '_blank')))
      )),
      '#type' => 'select',
      '#options' => $user_chooser_options,
      '#default_value' => isset($mcapi_form['2ndperson']['args']) ? $mcapi_form['2ndperson']['args'] : 'mcapi_access_check_roles:2'
    );
    $direction_widget = _macpi_forms_direction_widget($mcapi_form['direction']);
    $options = array(
      'outgoing' => isset($mcapi_form['direction']['incoming']) ? $mcapi_form['direction']['incoming'] : t('Request'),
      'incoming' => isset($mcapi_form['direction']['outgoing']) ? $mcapi_form['direction']['outgoing'] : t('Payment')
    );
    $form['direction'] = _mcapi_form_shared($mcapi_form, $direction_widget, 'direction');
    $form['direction']['shared']['preset']['#options'] = $options;
    //not sure these are ever used...
    $form['direction']['incoming'] = array(
      '#title' => t('Name of incoming transaction'),
      '#type' => 'textfield',
      '#default_value' => $options['incoming'],
    );
    $form['direction']['outgoing'] = array(
      '#title' => t('Name of outgoing transaction'),
      '#type' => 'textfield',
      '#default_value' => $options['outgoing'],
    );
    $form['direction']['widget'] = array(
      '#title' => t('Widget'),
      '#type' => 'select',
      '#options' => array(
        'select' => t('Dropdown select box'),
        'radios' => t('Radio buttons')
      ),
      '#default_value' => isset($mcapi_form['direction']['widget']) ? $mcapi_form['direction']['widget'] : 'outgoing',
    );
    $form['direction']['shared']['preset']['#description'] = t("Either 'incoming' or 'outgoing' relative to the logged in user");

  }
  //these are the field api fields which may have been added to the transaction entity
  $additional_fields = mcapi_form_get_formAPI_fields();
  foreach ($additional_fields as $fieldname => $field) {
    $form[$fieldname] = _mcapi_form_shared($mcapi_form, $transaction_fields[$fieldname], $fieldname);
  }

  $form['submission'] = array(
    '#title' => t('Form submission'),
    '#description' => t("Settings for @field", array('@field' => t('Submit'))),
    '#type' => 'fieldset',
    '#collapsible' => TRUE,
    '#collapsed' => !empty($form_id),
    'outgoing' => array(
      '#title' => t('Save to state'),
      '#description' => t('Transaction will be submitted in this state'),
      '#type' => 'select',
      '#options' => $outgoing_options,
      '#default_value' => empty($mcapi_form['submission']['outgoing']) ? TRANSACTION_STATE_FINISHED : $mcapi_form['submission']['outgoing'],
      '#weight' => 3,
    ),
    'button' => array(
      '#title' => t('Save button'),
      '#description' => t("The text to appear on the 'save' button, or the absolute url of an image"),
      '#type' => 'textfield',
      '#default_value' => isset($mcapi_form['submission']['button']) ? $mcapi_form['submission']['button'] : t('Submit'),
      '#required' => TRUE,
    ),
    'redirect' => array(
      '#title' => t('Destination'),
      '#description' => implode('<br />', array(
        t("Use token '%transaction' to refer to the transaction ID, and %user for the current user ID"),
        t("E.g. the transaction's own page is 'transaction/%transaction'."),
      )),
      '#type' => 'textfield',
      '#default_value' => isset($mcapi_form['submission']['redirect']) ? $mcapi_form['submission']['redirect'] : '',
      '#element_validate' => array('mcapi_path_validate'),
      '#required' => TRUE,
      '#weight' => 1,
    ),
    '#weight' => 20,
  );

  $form['mcapi_form_templates'] = array(
    '#type' => 'vertical_tabs',
    '#weight' => 7,
    '#prefix' => '<style>.vertical-tabs-panes div.form-type-radio{display:inline;}</style>',
  );
  $form['step-1'] = array(
    '#title' => t('Step 1 form'),
    '#description' => implode(' ', array(
      t('Write HTML here to layout the form, using the fieldnames in square brackets above as tokens.'),
      t('Allowed tags are:') .' a, p, br, font, style'
    )),
    '#type' => 'fieldset',
    '#group' => 'mcapi_form_templates',
    '#weight' => 1,
    'title' => array(
      '#title' => t('Title'),
      '#description' => t('For use in block title and menu links.') .' '. t('Plain text only.'),
      '#type' => 'textfield',
      '#default_value' => isset($mcapi_form['step-1']['title']) ? $mcapi_form['step-1']['title'] : '',
    ),
    'template' => array(
      '#title' => t('Main form'),
      '#type' => 'textarea',
      '#rows' => isset($mcapi_form['step-1']['template']) ? count(explode("\n", $mcapi_form['step-1']['template'])) : 5,
      '#default_value' => isset($mcapi_form['step-1']['template']) ? $mcapi_form['step-1']['template'] : '',
    ),
  );
  $form['step-2'] = array(
    '#title' => t('Step-2 form (optional)'),
    '#description' => implode(' ', array(
      t('Write HTML here to layout the form, using the fieldnames in square brackets above as tokens.'),
      t('Allowed tags are:') .' a, p, br, font, style'
    )),
    '#type' => 'fieldset',
    '#group' => 'mcapi_form_templates',
    '#weight' => 2,
    'title' => array(
      '#title' => t('Title'),
      '#description' => t('Plain text only.'),
      '#type' => 'textfield',
      '#default_value' => isset($mcapi_form['step-2']['title']) ? $mcapi_form['step-2']['title'] : '',
    ),
    'template' =>  array(
      '#title' => t('Confirmation page (optional)'),
      '#description' => t('This page will be shown after the transaction has validated but before the form is submitted.') .' '.
        t("Two more tokens are available here, '@certificate' and '@summary'", array('@certificate' => '[certificate]', '@summary' => '[summary]')),
      '#type' => 'textarea',
      '#rows' => isset($mcapi_form['step-2']['template']) ? count(explode("\n", $mcapi_form['step-2']['template'])) : 5,
      '#default_value' => isset($mcapi_form['step-2']['template']) ? $mcapi_form['step-2']['template'] : '',
      '#weight' => 2,
    )
  );
}


function _mcapi_form_shared($mcapi_form_fields, array $default_field, $key) {
  static $w;
  $element = array(
    '#title' => "[$key]",
    '#description' => t("Settings for '@field' field", array('@field' => $key)),
    '#type' => 'fieldset',
    '#group' => 'mcapi_form_fields',
    '#weight' => $w++,
    'shared' => array(
      '#title' => t('shared properties'),//this isn't showing, and nor is the border
      '#description' =>  '<strong>'.t('shared properties') .'</strong> '.t('What to do with passed parameters'),
      '#type' => 'fieldset',
      '#attributes' => array('style' => 'border:thin solid gray;width:300px;'),
      '#weight' => -5,
      'preset' => array(
        '#title' => t('Hard set value'),
        '#description' => t('overrides incoming values'),
      ) + $default_field,
      'disable' => array(
        '#title' => t('Grey out field if value is passed'),
        '#type' => 'checkbox',
        '#return_value' => TRUE,
        '#default_value' => isset($mcapi_form_fields[$key]['shared']['disable']) ? $mcapi_form_fields[$key]['shared']['disable'] : 'edit',
        '#weight' => 1,
      ),
      'required' => array(
        '#title' => t('Required'),
        '#description' => t('Form should fail validation if no value is submitted.'),
        '#type' => 'checkbox',
        '#default_value' => isset($mcapi_form_fields[$key]['shared']['required']) ? $mcapi_form_fields[$key]['shared']['required'] : '',
        '#weight' => 10
      ),
    ),
  );
  //set the defaults from the saved object
  $preset = &$mcapi_form_fields[$key]['shared']['preset'];
  if (isset($default_field['und'][0]['#columns'])) {//for formAPI fields
    foreach ($default_field['und'][0]['#columns'] as $column) {
      if (isset($preset['und']) && isset($preset['und'][0][$column])) {
        $element['shared']['preset']['und'][0][$column]['#default_value'] = $preset['und'][0][$column];
      }
    }
  }
  else { //for transaction entity fields
    $element['shared']['preset']['#default_value'] = $preset;
  }

  if (!empty($default_field['#required'])) {
    $element['shared']['required']['#default_value'] = TRUE;
    $element['shared']['required']['#disabled'] = TRUE;
  }
  mcapi_unset_recursive($element['shared'], '#required');
  return $element;
}

function mcapi_forms_ajax_settings($form, &$form_state) {
  return $form;
}


/*
 * form validation callback for edit_mcapi_form
 */
function mcapi_forms_template_validate($form, &$form_state) {
  if (empty($form['step-1']['template']['#value']) && empty($form['step-2']['template']['#value'])) {
    form_set_error('template-1', t('The form template is empty'));
  }
  foreach (array('1', '2') as $num) {
    $form_state['values']['step-1']['template'] = filter_xss($form['step-'.$num]['template']['#value'], array('a', 'p', 'br', 'font', 'style'));
    //each template must have a title
    if (!empty($form_state['values']['step-'.$num]['template']) && empty ($form_state['values']['step-'.$num]['template'])) {
      form_set_error(t('Every template must have a title'));
    }
  }
}
