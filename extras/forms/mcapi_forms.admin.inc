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
  $menus = module_exists('menu') ? 
    db_select('menu_custom', 'm')->fields('m', array('menu_name', 'title'))->execute()->fetchAllKeyed() :
    array();

  $form['#prefix'] = '<div id = "form-wrapper">';
  $form['#suffix'] = '</div>';
  //I think ctools is supposed to be handling this
  $form['info']['name']['#type'] = 'machine_name';
  $form['info']['name']['#machine_name']['exists'] = 'mcapi_forms_exists';

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
    '#collapsed' => empty($mcapi_form),
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

  $form['submission'] = array(
    '#title' => t('Form submission'),
    '#description' => t("Settings for @field", array('@field' => t('Submit'))),
    '#type' => 'fieldset',
    '#collapsible' => TRUE,
    '#collapsed' => empty($mcapi_form),
    'state' => array(
      '#title' => t('Save to state'),
      '#description' => t('Transaction will be submitted in this state'),
      '#type' => 'select',
      '#options' => array('' => t('Unchanged')) + mcapi_states(),
      '#default_value' => empty($mcapi_form['submission']['state']) ? TRANSACTION_STATE_FINISHED : $mcapi_form['submission']['state'],
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
  $form['step-'.MCAPIFORM_STEP_ENTER] = array(
    '#title' => t('Data entry step'),
    '#description' => implode("\n<br /> ", array(
      t('Write HTML here to layout the form, using the fieldnames in square brackets above as tokens.'),
      t('Allowed tokens are:') .implode(', ', matslats_list_tokens('mcapiform')),
      t('Allowed HTML tags are:') .' a, p, br, font, style'
    )),
    '#type' => 'fieldset',
    '#group' => 'mcapi_form_templates',
    '#weight' => 1,
    'title' => array(
      '#title' => t('Title'),
      '#description' => t('For use in block title and menu links.') .' '. t('Plain text only.'),
      '#type' => 'textfield',
      '#default_value' => isset($mcapi_form['step-'.MCAPIFORM_STEP_ENTER]['title']) ? $mcapi_form['step-'.MCAPIFORM_STEP_ENTER]['title'] : '',
    ),
    'template' => array(
      '#title' => t('Main form'),
      '#type' => 'textarea',
      '#rows' => 6,
      '#default_value' => isset($mcapi_form['step-'.MCAPIFORM_STEP_ENTER]['template']) ? $mcapi_form['step-'.MCAPIFORM_STEP_ENTER]['template'] : '',
    ),
  );
  $form['step-'.MCAPIFORM_STEP_CONFIRM] = array(
    '#title' => t('Data confirmation step'),
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
      '#default_value' => isset($mcapi_form['step-'.MCAPIFORM_STEP_CONFIRM]['title']) ? $mcapi_form['step-'.MCAPIFORM_STEP_CONFIRM]['title'] : '',
    ),
    'template' =>  array(
      '#title' => t('Confirmation page (optional)'),
      '#description' => t('This page will be shown after the transaction has validated but before the form is submitted.') .' '.
        t("Two more tokens are available here, '@certificate' and '@summary'", array('@certificate' => '[certificate]', '@summary' => '[summary]')),
      '#type' => 'textarea',
      '#rows' => isset($mcapi_form['step-'.MCAPIFORM_STEP_CONFIRM]['template']) ? count(explode("\n", $mcapi_form['step-'.MCAPIFORM_STEP_CONFIRM]['template'])) : 5,
      '#default_value' => isset($mcapi_form['step-'.MCAPIFORM_STEP_CONFIRM]['template']) ? $mcapi_form['step-'.MCAPIFORM_STEP_CONFIRM]['template'] : '',
      '#weight' => 2,
    )
  );
  $form['#submit'][] = 'menu_rebuild';
}


function _mcapi_form_shared($mcapi_form_fields, array $default_field, $key) {
  static $w;
  mcapi_unset_recursive($default_field, '#element_validate');
  $element = array(
    '#title' => "[mcapiform:$key]",
    '#description' => t("Edit instance settings.", array('@field' => $key)),
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
  else { //for transaction entity main fields like payer, state etc
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
  $values = &$form_state['values'];
  //filter the templates and check that each one with a body has a title
  foreach (array(MCAPIFORM_STEP_ENTER, MCAPIFORM_STEP_CONFIRM) as $step) {
    $stepname = 'step-'.$step;
    if (isset($values[$stepname]['template'])) {
      $values[$stepname]['template'] = filter_xss($values[$stepname]['template'], array('a', 'p', 'br', 'font', 'style'));
    }
    //each template must have a title
    if (!empty($values[$stepname]['template'])) {
      if (empty ($values[$stepname]['title'])) {
        form_set_error($stepname, t('Every template must have a title'));
      }
      else $notempty = TRUE;
    }
  }
  //check that at least step1 template or step2 template has text
  if (empty($notempty)) {
    form_set_error('template-1', t('The form template is empty'));
  }
  if (empty($values['step-'.MCAPIFORM_STEP_ENTER]['template'])) return;
  
  //check that direction widget is visible if its 1st person and direction is not preset
  $required_fields = array('worth');
  $required_fields += $values['perspective'] == 1 ? array('secondperson', 'direction') : array('payer', 'payee');
  foreach ($required_fields as $fieldname) {
    if (empty($values[$fieldname]['preset']) && strpos($values['step-'.MCAPIFORM_STEP_ENTER]['template'], "[mcapiform:$fieldname]") === FALSE) {
      form_set_error('step-'.MCAPIFORM_STEP_ENTER, t('Either preset @tokenname, or include that token in the data entry step.',
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
  unset($form_state['values']['name'], $form_state['values']['xfid']);

  $form_state['item']->data = $form_state['values'];
  $mcapiform = &$form_state['item']->data;
  //need to check that all required fields exist in the form.
  if (!empty($mcapiform['step-1']) && strlen($mcapiform['step-1']['template'])) {
    foreach ($mcapiform as $name => $prop) {
      if (!empty($prop['required']) && empty($prop['preset'])) {
        if (strpos($mcapiform['step-1']['template'], $name) == FALSE) {
          form_error($form['step-1']['template'], t("Required field '@name' must be used in the form Step 1", array('@name' => $name)));
        }
      }
    }
  }
}

/*
 * submit callback for edit form, declared in the ctools plugin $this->plugin['form']['submit'] in mcapi_forms_ui.inc
 * NOT NEEDED
function mcapi_edit_mcapi_form_submit($form, &$form_state) {}
 */
