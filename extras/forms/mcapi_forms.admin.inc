<?php
include ('mcapi_forms.inc');

/*
 * This is the form-builder form
 * For every field in the transaction form, this form allows it to be configured.
 * Its not geometrically perfect, but covers a lot of use cases without getting too complicated
 */
function _edit_mcapi_form(&$form, &$form_state) {
  $mcapi_form = &$form_state['item']->data;
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

  $form['#prefix'] = '<div id = "form-wrapper">';
  $form['#suffix'] = '</div>';
  //I think ctools is supposed to be handling this
  $form['info']['name']['#type'] = 'machine_name';
  $form['info']['name']['#machine_name']['exists'] = 'mcapi_forms_exists';
  $form['info']['name']['#maxlength'] = 32;
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

  $form['architecture'] = array(
    '#title' => t('Architecture'),
    '#description' => t("Every form is a menu callback with its own access control. Don't forget currencies have their own access control as well."),
    '#type' => 'fieldset',
    '#collapsible' => TRUE,
    '#collapsed' => !empty($mcapi_form),
    '#weight' => 3,
    'path' => array(
      '#title' => t('Path'),
      '#description' => t("unique path of this form. (without leading slash)"),
      '#type' => 'textfield',
      '#default_value' => isset($mcapi_form['architecture']['path']) ? $mcapi_form['architecture']['path'] : '',
      '#required' => TRUE,
      '#weight' => 1
    ),
    'menu_link' =>array(
      '#title' => t('Menu link location'),
      '#description' => t('Choose the menu, or menu item-type.') .' '. t('Some choices may not produce a menu item') .' '.
        t("For more info, see 'type' info at !link", array('!link' => l('drupal.org', 'http://api.drupal.org/api/drupal/modules--system--system.api.php/function/hook_menu/7'))),
      '#type' => 'select',
      '#default_value' => isset($mcapi_form['architecture']['menu_link']) ? $mcapi_form['architecture']['menu_link'] : 'MENU_CALLBACK',
      '#options' => drupal_map_assoc(array_keys(menu_get_menus())) + array(
        MENU_CALLBACK => t('No menu item'),
        MENU_LOCAL_TASK => 'MENU_LOCAL_TASK',
        MENU_DEFAULT_LOCAL_TASK => 'MENU_DEFAULT_LOCAL_TASK',
        MENU_LOCAL_ACTION => 'MENU_LOCAL_ACTION'
      ),
      '#element_validate' => array(),
      '#weight' => 2
    ),
    'access' =>array(
      '#title' => t('Access control'),
      '#description' => t("In addition to currency access control, and block access control, access to this form can be restricted."),
      '#type' => 'select',
      '#default_value' => isset($mcapi_form['architecture']['access']) ? $mcapi_form['architecture']['access'] : 'currency',
      '#options' => $access_options,
      '#element_validate' => array(),
      '#weight' => 3
    ),
    'use_block' =>  array(
      '#title' => t('Make block available'),
      '#description' => t('The block is named according the path.') .' '.
        t('Remember that blocks have their own caching and access control.'),
      '#type' => 'checkbox',
      '#default_value' => isset($mcapi_form['architecture']['use_block']) ? $mcapi_form['architecture']['use_block'] : '',
      '#element_validate' => array(),
      '#weight' => 4
    )
  );

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
  $transaction_fields = transaction_form($form, $form_state);

  //this section of the form allows the admin to handle the individual fields of the transaction form.
  //the fields are handled here one in each tab, each field having some shared settings and some specific ones.
  $form['mcapi_form_fields'] = array(
    '#type' => 'vertical_tabs',
    '#weight' => 6,
    '#prefix' => '<style>.vertical-tabs-panes div.form-type-radio{display:inline;}</style>',
  );
  //the participants of the transaction are either payer/payee or 1stperson/secondperson. This is called the 'perspective' of the form.
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
    }
  }
  else {//1st person form shows one field only, presuming the current user initiates the transaction
    $form['secondperson'] = _mcapi_form_shared($mcapi_form, $transaction_fields['payer'], 'secondperson');
    $form['secondperson']['#widget'] = 'user_chooser';//this is the only option that will work right now
    $form['secondperson']['args'] = array(
      '#title' => t('Users to choose from'),
      '#description' => t('Configure this widget more at !link',
         array('!link' => l('admin/config/people/user_chooser', 'admin/config/people/user_chooser', array('attributes'=>array('target'=> '_blank')))
      )),
      '#type' => 'select',
      '#options' => $user_chooser_options,
      '#default_value' => isset($mcapi_form['secondperson']['args']) ? $mcapi_form['secondperson']['args'] : 'mcapi_access_check_roles:2'
    );
    $form['direction'] = array(
      '#type' => 'radios',
      '#options' => array(
        'incoming' => t('Incoming'),
        'outgoing' => t('Outgoing'),
      ),
    );
    $options = array(
      '' => t('--No preset--'),
      'incoming' => isset($mcapi_form['direction']['incoming']) ? $mcapi_form['direction']['incoming'] : t('Request'),
      'outgoing' => isset($mcapi_form['direction']['outgoing']) ? $mcapi_form['direction']['outgoing'] : t('Payment')
    );
    $form['direction'] = _mcapi_form_shared($mcapi_form, $form['direction'], 'direction');
    $form['direction']['preset']['#options'] = $options;
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
      '#type' => 'radios',
      '#options' => array(
        'select' => t('Dropdown select box'),
        'radios' => t('Radio buttons')
      ),
      '#default_value' => isset($mcapi_form['direction']['widget']) ? $mcapi_form['direction']['widget'] : 'radios',
    );
    $form['direction']['preset']['#description'] = t("Either 'incoming' or 'outgoing' relative to the logged in user");
  }
  //these are the field api fields which may have been added to the transaction entity
  $additional_fields = field_info_instances('transaction', 'transaction');
  foreach ($additional_fields as $fieldname => $field) {
    mcapi_unset_recursive($transaction_fields[$fieldname], '#required');
    mcapi_unset_recursive($transaction_fields[$fieldname], '#element_validate');
    $form[$fieldname] = _mcapi_form_shared($mcapi_form, $transaction_fields[$fieldname], $fieldname);
  }
  //special settings for worth field
  $field = field_info_field('worth');
  if ($field['cardinality'] > 1) {
    $form['worth']['preset']['#element_validate'] = array('mcapi_form_validate_worth_preset');
  }
  else {
    $form['worth']['preset'][LANGUAGE_NONE][0]['#config_mode'] = TRUE;
  }

  //we need to know the view modes of the transaction entity, to choose for the preview page
  $entity = entity_get_info('transaction');
  foreach ($entity['view modes'] as $name => $mode) {
    if (in_array($name, array('search_index', 'search_result')))continue;
    $view_modes[$name] = $mode['label'];
  }

  $form['experience'] = array(
    '#title' => t('User experience'),
    '#description' => t('Deteriming how the form and the confirmation work for the user'),
    '#type' => 'fieldset',
    '#weight' => 15,
    'title' => array(
      '#title' => t('Title'),
      '#description' => t('For use in block title and menu links.') .' '. t('Plain text'),
      '#type' => 'textfield',
      '#default_value' => isset($mcapi_form['experience']['title']) ? $mcapi_form['experience']['title'] : '',
      '#weight' => 1,
    ),
    'template' => array(
      '#title' => t('Main form'),
      '#description' => t('Write HTML here to layout the form, using the tokens below'),
      '#type' => 'textarea',
      '#rows' => 6,
      '#default_value' => isset($mcapi_form['experience']['template']) ? $mcapi_form['experience']['template'] : '',
      '#weight' => 2,
    ),
    'token_tree' => array(
      '#theme' => 'token_tree',
      '#token_types' => array('mcapiform'),
      '#global_types' => FALSE,
      '#weight' => 3,
    ),
    'sure_title' => array(
      '#title' => t('Confirmation page title'),
      '#description' => t('Plain text'),
      '#type' => 'textfield',
      '#default_value' => isset($mcapi_form['experience']['sure_title']) ? $mcapi_form['experience']['sure_title'] : '',
      '#weight' => 4,
    ),
    'sure_format' => array(
      '#title' => t('Confirmation preview mode'),
      '#type' => 'radios',
      '#options' => $view_modes,
      '#default_value' => isset($mcapi_form['experience']['sure_format']) ? $mcapi_form['experience']['sure_format'] : 'certificate',
      '#weight' => 5,
    ),
    'sure_template' => array(
      '#title' => '('. t('Confirmation page') .')',
      '#description' => t('If using tokens mode, write HTML here to layout the confirmation, using the tokens above.'),
      '#type' => 'textarea',
      '#rows' => 4,
      '#default_value' => @$mcapi_form['experience']['sure_template'],
      '#weight' => 6,
    ),
  );
  $form['submission'] = array(
    '#title' => t('Form submission'),
    '#description' => t("Settings for @field", array('@field' => t('Submit'))),
    '#type' => 'fieldset',
    '#collapsible' => TRUE,
    '#collapsed' => empty($mcapi_form),
    'button' => array(
      '#title' => t('Save button'),
      '#description' => t("The text to appear on the 'save' button, or the absolute url of an image"),
      '#type' => 'textfield',
      '#default_value' => isset($mcapi_form['submission']['button']) ? $mcapi_form['submission']['button'] : t('Submit'),
      '#required' => TRUE,
    ),
    'redirect' => array(
      '#title' => t('Destination'),
      '#description' => t('Goes to the new transaction certificate by default'),
      '#type' => 'textfield',
      '#default_value' => isset($mcapi_form['submission']['redirect']) ? $mcapi_form['submission']['redirect'] : '',
      '#element_validate' => array('mcapi_path_validate'),
      '#weight' => 1,
    ),
    'feedback' => array(
      '#title' => t('Message'),
      '#description' => t('Contents of the message box'),
      '#type' => 'textfield',
      '#default_value' => isset($mcapi_form['submission']['feedback']) ? $mcapi_form['submission']['feedback'] : '',
      '#weight' => 1,
    ),
    '#weight' => 20,
  );
}


function _mcapi_form_shared($mcapi_form_fields, array $default_field, $key) {
  static $w;
  mcapi_unset_recursive($default_field, '#element_validate');
  $element = array(
    '#title' => "[mcapiform:$key]",
    '#type' => 'fieldset',
    '#group' => 'mcapi_form_fields',
    '#weight' => $w++,
    'preset' => array(
      '#title' => t('Hard set value'),
      '#description' => t('overrides incoming values'),
    ) + $default_field,
  );
  //set the defaults from the saved object
  $preset = &$mcapi_form_fields[$key]['preset'];
  $lang = current(element_children($default_field)); //assumes value is in one language only
  if (is_array($preset) && isset($default_field[$lang][0]['#columns'])) {//for formAPI fields
    $element['#description'] = l(t("Edit instance settings."), 'admin/accounting/fields/'.$default_field[$lang][0]['#field_name']);
    unset($default_field[$lang]['add_more']);
    foreach (element_children($default_field[$lang]) as $delta) {
      foreach (element_children($default_field[$lang][$delta]) as $subfieldname) {
        if (!empty($preset[$lang][$delta][$subfieldname])) {
          $element['preset'][$lang][$delta][$subfieldname]['#default_value'] = $preset[$lang][$delta][$subfieldname];
        }
      }
    }
  }
  else { //for transaction entity main fields like payer etc
    $element['#description'] = t("This field is required");
    $element['preset']['#default_value'] = $preset;
  }
  return $element;
}

function mcapi_forms_ajax_settings($form, &$form_state) {
  return $form;
}


/*
 * form validation callback for edit_mcapi_form
 */
function mcapi_forms_template_validate($form, &$form_state) {
  register_shutdown_function('menu_rebuild');
  $values = &$form_state['values'];
  if (empty($values['experience']['template'])) return;
  //check that the critical transaction fields are either present in the template OR preset
  $required_fields = array('worth');
  $required_fields += $values['perspective'] == 1 ? array('secondperson', 'direction') : array('payer', 'payee');
  foreach ($required_fields as $fieldname) {
    if (empty($values[$fieldname]['preset']) && strpos($values['experience']['template'], "[mcapiform:$fieldname]") === FALSE) {
      form_set_error('template', t('Either preset @tokenname, or include that token in the template.',
        array('@tokenname' => "[mcapiform:$fieldname]")
      ));
    }
  }
}

function mcapi_forms_exists($machine_name, $element, &$form_state) {
  $forms = mcapi_forms_load();
  return isset($forms['machine_name']);
}


/*
 * validate callback for edit form, declared in the ctools plugin $this->plugin['form']['submit'] in mcapi_forms_ui.inc
 * the $form_state[item] is the object to be saved, derived from $form_state[values]
 */
function mcapi_edit_mcapi_form_validate($form, &$form_state) {
  form_state_values_clean($form_state);
  //we need to start with an existing ctools object which contains essential default properties
  if (empty($form_state['item'])) {
    $form_state['item'] = ctools_export_crud_new('mcapi_forms');
  }
  
  $form_state['item']->name = $form_state['values']['info']['name'];//this isn't editable at the moment
  unset($form_state['values']['name']);

  $form_state['item']->data = $form_state['values'];
  $mcapiform = &$form_state['item']->data;
  //need to check that all required fields exist in the form.
  if (!empty($mcapiform['experience']['template'])) {
    foreach ($mcapiform as $name => $prop) {
      if (!empty($prop['required']) && empty($prop['preset'])) {
        if (strpos($mcapiform['experience']['template'], $name) == FALSE) {
          form_error($form['experience']['template'], t("Required field '@name' must be used in the form template", array('@name' => $name)));
        }
      }
    }
  }
  cache_clear_all('mcapi_forms', 'cache');
}

/*
 * submit callback for edit form, declared in the ctools plugin $this->plugin['form']['submit'] in mcapi_forms_ui.inc
 * NOT NEEDED
function mcapi_edit_mcapi_form_submit($form, &$form_state) {}
 */

/*
 * element validate callback for worth field with multiple cardinality
 * if a default value is provided for any currency(s), then remove all other empty values
 * before saving it as a preset
 */
function mcapi_form_validate_worth_preset(&$element, &$form_state) {
  //if any of the currencies has been preset with a value, remove the empty ones
  $items = &$form_state['values']['worth']['preset']['und'];
  $allempty = TRUE;
  foreach ($items as $item) {
    if (!mcapi_field_is_empty($item)) $allempty = FALSE;
  }
  if ($allempty) return;
  //delete the empty items
  foreach ($items as $delta => $item) {
    if (mcapi_field_is_empty($item)) unset($items[$delta]);
  }
}