<?php
/*
 * This is the form-builder form
 * For every field in the transaction form, this form allows it to be configured.
 * Its not geometrically perfect, but covers a lot of use cases without getting too complicated
 */
function _mcapi_edit_webform(&$form, &$form_state) {
  $webform = &$form_state['item'];
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
  $form['info']['name']['#machine_name']['exists'] = 'mcapi_webforms_load';

  $options = array(
    3 => t('3rd Party'),
    1 => t('Participant')
  );
  $form['aspect'] = array(
    '#title' => t('Aspect'),
    '#description' => t('Whether this is completed by a 3rd party or a participant.'),
    '#type' => 'radios',
    '#options' => $options,
    '#default_value' => isset($form_state['values']['aspect']) ? $webform->data['aspect'] : (isset($webform->data['aspect']) ? $webform->data['aspect'] : 3),
    '#weight' => 1,
    //this ajax only works once then the form #id increments to ctools-export-ui-edit-item-form--2
    '#ajax' => array(
      'callback' => 'mcapi_webforms_ajax_settings',
      'wrapper' => 'form-wrapper'
    ),
  );
  //to cover up the fact that we can only reload the form once
  if (isset($form_state['values']['aspect'])) {
    //only include the selected option
    $form['aspect']['#options'] = array($form_state['values']['aspect'] => $options[$form_state['values']['aspect']]);
    $form['aspect']['#description'] = 'Technical limitations prohibit this being flipped more than once. Reload the whole page to change it back';
  }

  $form['xfid'] = array(
    '#type' => 'hidden',
    '#value' => isset($webform->xfid) ?$webform->xfid : ''
  );
  $form['path'] = array(
    '#title' => t('Path'),
    '#description' => t('url of this form. Use % to represent an existing transaction ID.'),
    '#type' => 'textfield',
    '#default_value' => isset($webform->path) ? $webform->path : '',
    '#element_validate' => array('mcapi_path_validate'),
    '#required' => TRUE,
    '#weight' => 2

  );
  $form['help'] = array(
    '#title' => t('Help'),
    '#description' => t('Tell the user what this form is for (optional).'),
    '#type' => 'textfield',
    '#default_value' => isset($webform->data['help']) ? $webform->data['help'] : '',
    '#maxlength' => 255,
    '#weight' => 3
  );
  $form['outputs'] = array(
    '#title' => t('Output'),
    '#description' => t("Every form is a menu callback with its own access control. Don't forget currencies have their own access control as well."),
    '#type' => 'fieldset',
    '#collapsible' => TRUE,
    '#collapsed' => !empty($webform->name),
    '#weight' => 4,
    'access' =>array(
      '#title' => t('Access control'),
      '#description' => t("In addition to currency access control, and block access control, access to this form can be restricted."),
      '#type' => 'select',
      '#default_value' => isset($webform->data['outputs']['access']) ? $webform->data['outputs']['access'] : 'currency',
      '#options' => $access_options,
      '#element_validate' => array(),
    ),
    'menu_link' =>array(
      '#title' => t('Menu link location'),
      '#description' => t('Choose the menu, or menu item-type.') .' '. t("For more info, see 'type' info at !link", array('!link' => l('drupal.org', 'http://api.drupal.org/api/drupal/modules--system--system.api.php/function/hook_menu/7'))),
      '#type' => 'select',
      '#default_value' => isset($webform->data['outputs']['menu_link']) ? $webform->data['outputs']['menu_link'] : 'MENU_CALLBACK',
      '#options' => $menus+ array(
        MENU_CALLBACK => t('No menu item'),
        MENU_LOCAL_TASK => 'MENU_LOCAL_TASK',
        MENU_DEFAULT_LOCAL_TASK => 'MENU_DEFAULT_LOCAL_TASK',
        MENU_LOCAL_ACTION => 'MENU_LOCAL_ACTION'
      ),
      '#element_validate' => array(),
    ),
    'use_block' =>  array(
      '#title' => t('Output block'),
      '#description' => t('The block is named according the path.') .' '.
        t('Remember that blocks have their own caching and access control.'),
      '#type' => 'checkbox',
      '#default_value' => isset($webform->data['outputs']['use_block']) ? $webform->data['outputs']['use_block'] : '',
      '#element_validate' => array(),
    )
  );
  
  $outgoing_options = mcapi_transaction_states();
  unset($outgoing_options[-2]);//as far as forms are concerned, TRANSACTION_STATE_VALIDATING is invisible
  $incoming_options = $outgoing_options;
  unset($incoming_options[-1]);//erase transactions should never be editable
  unset($outgoing_options['unset']);
  $outgoing_options['null'] = '('.t('inherit').')';

  $form['workflow'] = array(
    '#title' => t('Workflow'),
    '#description' => t('Beyond simply creating and editing transactions, these forms move the transactions between states'),
    '#type' => 'fieldset',
    '#collapsible' => TRUE,
    '#collapsed' => !empty($webform->name),
    'incoming' => array(
      '#title' => t('Allow only incoming states'),
      '#description' => t('Form will not build if incoming transaction is in the wrong state'),
      //this should be checkboxes, but there's a problem with checkboxes and select multiple for values with a key of 0
      '#type' => 'checkboxes',
      '#options' => drupal_map_assoc($incoming_options),
      '#default_value' => isset($webform->data['workflow']['incoming']) ? $webform->data['workflow']['incoming'] : array('Unset'),
      '#weight' => 1,
    ),
    'outgoing' => array(
      '#title' => t('Save to state'),
      '#description' => t('Transaction will be submitted in this state'),
      '#type' => 'select',
      '#options' => $outgoing_options,
      '#default_value' => isset($webform->data['workflow']['outgoing']) ? $webform->data['workflow']['outgoing'] : TRANSACTION_STATE_FINISHED,
      '#weight' => 3,
    ),
    '#weight' => 5,
  );

  $form['webform_fields'] = array(
    '#type' => 'vertical_tabs',
    '#weight' => 6,
    '#prefix' => '<style>.vertical-tabs-panes div.form-type-radio{display:inline;}</style>',
  );

  //special settings for cid
  $form['cid'] = mcapi_webform_prefill_fields($webform, 'cid', t('Currency ID'));
  $options = currency_select(currency_choose('initiate', 1));//that's uid 1 to make all currencies available
  $form['cid']['options'] = array(
    '#title' => t('Limit choosable currencies to'),
    '#description' => t('To not limit, check nothing.'),
    '#type' => 'checkboxes',
    '#options' => $options,
    '#default_value' => isset($webform->data['cid']['options']) ? $webform->data['cid']['options'] : array(),
  );
  if (count($options) == 1) {
    $form['cid']['preset']['#default_value'] = array_pop(array_keys($options));
    $form['cid']['widget'] = array(
      '#value' => 'hidden',
      '#type' => 'hidden',
    );
  }
  else{
    $form['cid']['widget'] = array(
      '#title' => t('Widget'),
      '#type' => 'radios',
      '#options' => array(
        'select' => t('Dropdown select box'),
        'radios' => t('Radio buttons')
      ),
      '#default_value' => empty($webform->data['cid']['widget']) ? 'select' : $webform->data['cid']['widget']
    );
  }
  
  if ($form['aspect']['#default_value'] == 3) {
    //special settings for payer and payee
    foreach (array('payer' => t('Payer ID'), 'payee' => t('Payee ID')) as $key => $name) {
      $form[$key] = mcapi_webform_prefill_fields($webform, $key, $name);
      $form[$key]['#widget'] = 'user_chooser';//this is the only option that will work right now
      $form[$key]['args'] = array(
        '#title' => t('Users to choose from'),
        '#description' => t('Configure this widget more at !link',
           array('!link' => l('admin/config/people/user_chooser', 'admin/config/people/user_chooser', array('attributes'=>array('target'=> '_blank')))
        )),
        '#type' => 'select',
        '#options' => $user_chooser_options,
        '#default_value' => isset($webform->data[$key]['args']) ? $webform->data[$key]['args'] : 'mcapi_access_check_roles:2'
      );
      $form[$key]['preset']['#description'] = t('Use [self] to preset with the logged in user');
      $form[$key]['multiple'] = array(
        '#title' => t('Multiple users'),
        '#description' => t('Allow multiple users and repeat the transaction for each of them'),
        '#type' => 'checkbox',
        '#default_value' => isset($webform->data[$key]['multiple']) ? $webform->data[$key]['multiple'] : FALSE,
      );
    }
  }
  else {//1st person form shows one field only, presuming the current user initiates the transaction
    $form['2ndperson'] = mcapi_webform_prefill_fields($webform, '2ndperson', t('2nd Person'));
    $form['2ndperson']['#widget'] = 'user_chooser';//this is the only option that will work right now
    $form['2ndperson']['args'] = array(
      '#title' => t('Users to choose from'),
      '#description' => t('Configure this widget more at !link',
         array('!link' => l('admin/config/people/user_chooser', 'admin/config/people/user_chooser', array('attributes'=>array('target'=> '_blank')))
      )),
      '#type' => 'select',
      '#options' => $user_chooser_options,
      '#default_value' => isset($webform->data['2ndperson']['args']) ? $webform->data['2ndperson']['args'] : 'mcapi_access_check_roles:2'
    );
    $form['direction'] = mcapi_webform_prefill_fields($webform, 'direction', t('Direction'));
    $form['direction']['incoming'] = array(
      '#title' => t('Name of incoming transaction'),
      '#type' => 'textfield',
      '#default_value' => isset($webform->data['direction']['incoming']) ? $webform->data['direction']['incoming'] : t('Request'),
    );
    $form['direction']['outgoing'] = array(
      '#title' => t('Name of outgoing transaction'),
      '#type' => 'textfield',
      '#default_value' => isset($webform->data['direction']['outgoing']) ? $webform->data['direction']['outgoing'] : t('Payment'),
    );
    $form['direction']['widget'] = array(
      '#title' => t('Widget'),
      '#type' => 'select',
      '#options' => array(
        'select' => t('Dropdown select box'),
        'radios' => t('Radio buttons')
      ),
      '#default_value' => isset($webform->data['direction']['widget']) ? $webform->data['direction']['widget'] : 'outgoing',
    );
    $form['direction']['common']['preset']['#description'] = t("Either 'incoming' or 'outgoing'");
  }
  //special settings for amount
  $form['amount'] = mcapi_webform_prefill_fields($webform, 'amount', t('Amount'));
  $form['amount']['chars'] = array(
    '#title' => t('Character width of the integer field'),
    '#description' => t('Integer html width and maxlength attributes'),
    '#type' => 'textfield',
    '#default_value' => $webform->data['amount']['chars'],
    '#size' => 2,
    '#element_validate' => array('_element_validate_integer'),
  );

  //special settings for description
  $form['description'] = mcapi_webform_prefill_fields($webform, 'description', t('Description'));
  
  $additional_fields = mcapi_webform_get_additional_form_fields();
  foreach ($additional_fields as $name => $field) {
    $form[$name] = mcapi_webform_prefill_fields_additional($webform, $name, $field);
  }

  $form['submission'] = array(
    '#title' => t('Form submission'),
    '#description' => t("Settings for @field", array('@field' => t('Submit'))),
    '#type' => 'fieldset',
    '#collapsible' => TRUE,
    '#collapsed' => !empty($webform->name),
    'button' => array(
      '#title' => t('Save button'),
      '#description' => t("The text to appear on the 'save' button, or the absolute url of an image"),
      '#type' => 'textfield',
      '#default_value' => isset($webform->data['submission']['button']) ? $webform->data['submission']['button'] : t('Submit'),
      '#required' => TRUE,
    ),
    'redirect' => array(
      '#title' => t('Destination'),
      '#description' => implode('<br />', array(
        t("Use token '#xid' to refer to the transaction id."),
        t("E.g. the transaction's own page is 'transaction/#xid'."),
        t("All 'Validating' transactions should point to another form.")
      )),
      '#type' => 'textfield',
      '#default_value' => isset($webform->data['submission']['redirect']) ? $webform->data['submission']['redirect'] : '',
      '#element_validate' => array('mcapi_path_validate'),
      '#required' => TRUE,
      '#weight' => 1,
    ),
    '#weight' => 20,
  );
  $form['templates'] = array(
    '#title' => t('Templates'),
    '#description' => implode(' ', array(
      t('Write HTML here to layout the form, using the fieldnames in square brackets above as tokens.'),
      t('Allowed tags are:') .' a, p, br, font, style'
    )),
    '#type' => 'fieldset',
    '#collapsible' => TRUE,
    '#collapsed' => !empty($webform->name),
    '#weight' => 7,
    'title-1' => array(
      '#title' => t('Title'),
      '#description' => t('For use in block title and menu links'),
      '#type' => 'textfield',
      '#default_value' => isset($webform->data['templates']['title-1']) ? $webform->data['templates']['title-1'] : '',
      '#required' => TRUE
    ),
    'template-1' => array(
      '#title' => t('Main form'),
      '#type' => 'textarea',
      '#rows' => isset($webform->data['templates']['template-1']) ? count(explode("\n", $webform->data['templates']['template-1'])) : 5,
      '#default_value' => isset($webform->data['templates']['template-1']) ? $webform->data['templates']['template-1'] : '',
      '#element_validate' => array('mcapi_webforms_template_validate'),
      '#required' => TRUE,
    ),
    'title-2' => array(
      '#title' => t('Title'),
      '#type' => 'textfield',
      '#default_value' => isset($webform->data['templates']['title-2']) ? $webform->data['templates']['title-2'] : '',
    ),
    'template-2' =>  array(
      '#title' => t('Confirmation page (optional)'),
      '#description' => t('This page will be shown after the transaction has validated but before the form is submitted.') .' '.
        t("Two more tokens are available here, '@certificate' and '@summary'", array('@certificate' => '[certificate]', '@summary' => '[summary]')),
      '#type' => 'textarea',
      '#rows' => isset($webform->data['templates']['template-2']) ? count(explode("\n", $webform->data['templates']['template-2'])) : 5,
      '#default_value' => isset($webform->data['templates']['template-2']) ? $webform->data['templates']['template-2'] : '',
      '#weight' => 2,
    )
  );
}


function mcapi_webform_prefill_fields($webform, $key, $name) {
  static $w;
  return array(
    '#title' => "[$key]",
    '#description' => t("Settings for @field", array('@field' => $name)),
    '#type' => 'fieldset',
    '#group' => 'webform_fields',
    '#weight' => $w++,
    'common' => array(
      '#title' => t('Common properties'),//this isn't showing, and nor is the border
      '#description' =>  '<strong>'.t('Common properties') .'</strong> '.t('What to do with passed parameters'),
      '#type' => 'fieldset',
      '#attributes' => array('style' => 'border:thin solid gray;width:350px;'),
      '#weight' => -5,
      'preset' => array(
        '#title' => t('Hard set value'),
        '#description' => t('overrides incoming values'),
        '#type' => 'textfield',
        '#default_value' => isset($webform->data[$key]['common']['preset']) ? $webform->data[$key]['common']['preset'] : '',
        '#size' => 3,
      ),
      'disable' => array(
        '#title' => t('Grey out field if value is passed'),
        '#type' => 'checkbox',
        '#return_value' => TRUE,
        '#default_value' => isset($webform->data[$key]['common']['disable']) ? $webform->data[$key]['common']['disable'] : 'edit',
        '#weight' => 1,
      ),
      'required' => array(
        '#title' => t('Required'),
        '#description' => t('Form should fail validation if no value is submitted.'),
        '#type' => 'checkbox',
        '#default_value' => isset($webform->data[$key]['common']['required']) ? $webform->data[$key]['common']['required'] : '',
        '#weight' => 10
      ),
    ),
  );
}

function mcapi_webforms_ajax_settings($form, &$form_state) {
  return $form;
}
/*
 * wrapper function to build default settings form for additional fields
 */
function mcapi_webform_prefill_fields_additional(&$webform, $key, $field) {
  $element = get_deepest_child($field);
  $form = mcapi_webform_prefill_fields($webform, $key, $element['#title']);
  if (!empty($element['#required'])) {
    $form['common']['required']['#default_value'] = TRUE;
    $form['common']['required']['#disabled'] = TRUE;
  }
  return $form;
}

function mcapi_webforms_template_validate(&$element, &$form_state) {
  $main_template =  filter_xss($element['#value'], array('a', 'p', 'br', 'font', 'style', ));
  foreach ($form_state['values'] as $fieldname => $value) {
    if (is_array($value) && !empty($value['common']['required'])) {
      if (!strpos($main_template, $fieldname)) {
        form_error($element, t('Required value @title has no preset and is not included in the template', array('@title' => $fieldname)));
      }
    }
  }
  $form_state['values']['templates']['template-1'] = $main_template;
  //TODO: check that all forms which output state 'Validating' redirect to another webform
}


//this is completely wrong, but I just couldn't get ctools to work without proper documentation
function mcapi_edit_webform_submit($form, &$form_state) {
  $values = &$form_state['values'];
  unset($values['form_id'], $values['form_token'], $values['op'], $values['form_build_id'], $values['submit'], $values['xfid']);
  if (isset($form_state['item'])) {
    $webform = &$form_state['item'];
  }
  else {
    ctools_export_crud_new('mcapi_webforms');
  }
  $webform->name = $values['info']['name'];//this isn't editable at the moment
  $webform->path = $values['path'];
  unset($values['name'], $values['path']);
  $webform->data = $values;
  //this should be used in conjunction with form_alter
  drupal_alter('mcapi_webform', $webform, $values);

  $form_state['redirect'] = 'admin/accounting/webforms';
  register_shutdown_function('menu_rebuild');
}

function mcapi_path_validate(&$element, $form_state) {
  if (!valid_url($element['#value'])) {
    form_error($element, t('The provided URL is invalid.'));
  }
}
