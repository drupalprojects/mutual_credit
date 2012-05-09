<?php

define ('CURRENCY_DIVISION_MODE_NONE', 0);
define ('CURRENCY_DIVISION_MODE_CENTS_INLINE', 1);
define ('CURRENCY_DIVISION_MODE_CENTS_FIELD', 2);
define ('CURRENCY_DIVISION_MODE_CUSTOM', 3);

/*
 * Imlements entity module hook_field_info
 */
function mcapi_field_info() {
  return array(
    'worth_field' => array(
      'label' => t('Worth'),
      'description' => t("Floating point value to two decimal places"),
      'settings' => array('allowed_values' => array(), 'allowed_values_function' => ''),
      'default_widget' => 'worth_widget',
      'default_formatter' => 'worth_widget',
      'translatable' => FALSE,
      //following properties are for the entity API module
      'property_type' => 'worth',//don't know what this refers to
      'property_callbacks' => array('entity_metadata_field_worth_property_callback'),
    )
  );
}

/*
 * implements hook_field_widget_info
 */
function mcapi_field_widget_info() {
  return array(
    'worth_widget' => array(
      'label' => t('Worth'),
      'field types' => array('worth_field'),
    )
  );
}
/*
 * implements hook_field_formatter_info
 */
function mcapi_field_formatter_info() {
  return array(
    'worth_field' => array(
      'label' => t('Worth'),
      'field types' => array('worth_field'),
    )
  );
}

/*
 * implements hook_field_formatter_view
 * prepare a render array containing all
 * not sure if this poor design of the FieldAPI, but sometimes the items passed are form fields and sometimes saved values -
 * in this module they are different coz we save the float but the form may show 2 fields
 */
function mcapi_field_formatter_view($entity_type, $entity, $field, $instance, $langcode, $items, $display) {
  foreach($items as $delta => $item) {
    if (!isset($item['quantity'])) {
      $item['quantity'] = $item['main_quant'];
      $cents_division_types = array(CURRENCY_DIVISION_MODE_CENTS_FIELD, CURRENCY_DIVISION_MODE_CUSTOM);
      $currency_division_type = currency_load($item['currcode'])->display['divisions'];
      if (in_array($currency_division_type, $cents_division_types)) {
        //add them together as strings, with a decimal point
        $item['quantity'].= '.'. $item['division_quant'];
      }
    }
    $element[$delta] = array(
      '#theme' => 'worth_field',
      '#currcode' => $item['currcode'],
      '#quantity' => $item['quantity'],
    );
  }
  return $element;
}

/*
 * implements hook_field_widget_form
 * this is called once for each delta value
 * If a default currcode is given, then it preformats the field
 * Otherwise it generates a currcode select list, with ajax
 */
function mcapi_field_widget_form($form, &$form_state, $field, $instance, $langcode, $items, $delta, $element) {
  $element['#delta'] = $delta;
  $item = &$items[$delta];
  list($default_vals['main_quant'], $default_vals['division_quant']) = currency_explode($item['quantity']);
  $element['main_quant'] = array(
    '#title' => t('Units'), //this is only ever seen on the error message anyway
    '#type' => 'textfield',
    '#default_value' => $default_vals['main_quant'],
    '#size' => 4,
    '#maxlength' => 6,
    '#weight' => 1
  );
  if($field['cardinality'] > 1) {//if there are many currency fields, the default for each is fixed here
    $currcodes = array_keys(currencies_load($GLOBALS['user']));
    $currcode = $currcodes[$delta];
    //if the worth field was preset - new_transaction() doesn't preset this field
    if (empty($form_state['values']) && isset($item)) {
      //so we only show the currencies listed as items
      //that means we might want to skip this widget form if its not one of the preset items
      if ($item['currcode'] == $currcode) {
        $element['#restrict_currcodes'] = array($currcode);
      }
      else return;
    }
    else {//no presets, so make each $item into a field for each currency
      $element['#restrict_currcodes'] = array($currcode);
    }
  }
  elseif (isset($item['currcode'])) {//cardinality is 1 and the currency is already set
    $element['#restrict_currcodes'] =  array($item['currcode']);
  }
  worth_element_build($element, $default_vals);
  $element['#element_validate'] = array('worth_widget_validate');
  $element['#process'] = array('mcapi_field_widget_process');
  $element['#theme'] = 'worth_widget';
  return $element;
}

/*
 * form process callback
 * just processes one 'worth' at a time
 * creates each of the element children, currcode, integer, division
 * N.B there are 3 form elements but the widget returns 2 values currcode and quantity (float)
 * this is run during the ajax rebuild, whereas hook_field_widget_form isn't
 * so this is the stage that formats the divisions sub-widget according to currcode, which may have changed since the form was first built
 * tricky bit is setting the field[#default_value]s which are sometimes raw field data and sometimes from the db, sometimes passed in
 */
function mcapi_field_widget_process($element, &$form_state, $form) {
  //determine the values for each of the three subfields, main_quant, division_quant, currcode
  //these are handled in order of priority as the default values could come from different sources
  //deal with any submitted values from ajax, or the back button
  if (isset($form_state['transaction']->worth['und'][$element['#delta']])) {
    $value = $form_state['transaction']->worth['und'][$element['#delta']];
    //$default_vals['currcode'] = $value['currcode'];
    $element['#restrict_currcodes'] = array($value['currcode']);
    list($default_vals['main_quant'], $default_vals['division_quant']) = currency_explode($value['quantity']);
    //coz its ajax, we rebuild most of the element
  }
  else {
    foreach (element_children($element) as $fieldname) {
      $default_vals[$fieldname] = @$element[$fieldname]['#default_value'];
    }
  }
  worth_element_build($element, $default_vals);
  foreach (element_children($element) as $child) {
    $element[$child]['#theme_wrappers'] = array();
  }

  //set the weight in case this is a multiple currency form
  return $element;
}
/*
 * because the structure of the widget itself depends on the currcode,
 * and the currcode is sometimes supplied by ajax,
 * and ajax doesn't rebuild the whole form, but only reprocesses it
 * And because sometimes we need the filled form including defaults before processing --when?
 * this function usually runs twice! Called from both the form build function and the process function
 */
function worth_element_build(&$element, $default_vals) {
  static $cardinality;//because this function usually runs twice per form
  if (empty($cardinality)) {
    $field = field_info_field('worth');
    $cardinality = $field['cardinality'];
  }
  $currcodes = isset($element['#restrict_currcodes']) ? 
    $element['#restrict_currcodes'] :
    array_keys(currencies_load($GLOBALS['user']));

  //call the standard currency choosing widget
  $element['currcode'] = currency_picker_element($currcodes, @$default_vals['currcode']) + array(
    '#ajax' => array(//seems like the ajax is necessary on the rebuilt form, even if the field is hidden
      'callback' => 'mcapi_ajax_worth',
      'wrapper' => 'worth-add-more-wrapper'
    ),
  );
  $currcode = isset($element['currcode']['#value']) ? $element['currcode']['#value'] : $element['currcode']['#default_value'];
  if (empty($currcode)) return;
  $currency = currency_load($currcode);

    //Only show the divisions element if we know the currency and it has divisions. We are already showing integers
  if ($currency->display['divisions'] == CURRENCY_DIVISION_MODE_CENTS_FIELD) {
    $element['division_quant'] =  array(
      '#title' => t('Cents'),
      '#type' => 'textfield',
      '#size' => 2,
      '#maxlength' => 2,
      '#field_prefix' => '.',
      '#default_value' => @$default_vals['division_quant'],
      '#element_validate' => array('element_validate_integer_worth'),
      '#weight' => 2
    );
  }
  elseif ($currency->display['divisions'] == CURRENCY_DIVISION_MODE_CUSTOM){
    $element['division_quant'] = array(
      '#title' => t('Fraction'),
      '#options' => $currency->display['divisions_allowed'], //which MUST be an array because of _currency_divisions_array
      '#type' => 'select',
      '#field_prefix' => ':',
      '#default_value' => @$default_vals['division_quant'],
      '#element_validate' => array('element_validate_integer_worth'),
      '#weight' => 2
    );
  }
  //If the display format requires we can re-join the main part and the division if they belong in one widget
  if (in_array($currency->display['divisions'], array(CURRENCY_DIVISION_MODE_CENTS_INLINE, CURRENCY_DIVISION_MODE_CENTS_FIELD))) {
    $element['main_quant']['#element_validate'] = array('element_validate_number'); //will do for now
  }
  else {
    $element['main_quant']['#element_validate'] = array('element_validate_integer_worth');
  }
}

/*
 * element validation callback
 * convert the split form field quantity into one number for easier processing and storage
 * this is then used in the $items for the official widget validation mcapi_field_validate
 * $element[#value] should already be set
 */
function worth_widget_validate(&$element, &$form_state, $form) {
  if ($form['#form_id'] == 'field_ui_field_edit_form') return;
  $value['currcode'] = &$element['currcode']['#value'];
  $value['quantity']  = &$element['main_quant']['#value'];
  if (!empty($element['division_quant']['#value'])) {
    //combine integer and division to get quantity
    $value['quantity'] += $element['division_quant']['#value']/100;
  }
  $element['#value'] = $value;
  //this affects form_state only, which later is used to set $element['#value

  form_set_value($element, $value, $form_state);
}

/*
 * implements hook_field_is_empty
 * tests to see if a field is empty
 * $item is the raw field data i.e. integer and division
 */
function mcapi_field_is_empty($item) {
  return empty($item['main_quant']) && empty($item['division_quant']);
}

/*
 * implements hook_field_validate
 * check if the current user has permission to use the currency in this flow.
 * runs AFTER worth_widget_validate
 * each item is an array(currcode => varchar, integer => integer, division => integer)
 * Pays particular attention to the currcodes
 */
function mcapi_field_validate($entity_type, $entity, $field, $instance, $langcode, $items, &$errors) {
  $permitted_currencies = currencies_load($GLOBALS['user']);
  foreach ($items as $delta => $item) {
    if (!isset($permitted_currencies[$item['currcode']])) {
      $errors[$field['field_name']][$langcode][$delta][] = array(
        'error' => 'mcapi_invalid_currency',//this is not working as intended
        'message' => t('This currency does not exist, or you do not have permission to use it'),
      );
    }
    $currency = currency_load($item['currcode']);
    $quant = $item['quantity'] + 0; //convert from string
    if (!is_int($quant)) {
      $division = $quant - intval($quant);
      //check that divisions match allowed values for the currency
      if (count($currency->display['divisions_allowed']) && !isset($currency->display['divisions_allowed'][$division])) {
        $errors[$field['field_name']][$langcode][$delta][] = array(
          'error' => 'mcapi_invalid_division',//this is not working as intended
          'message' => t('Only certain centiles are permitted for @name', array('@name' => $currency->human_name)),
        );
      }
    }
    if ($item['quantity'] <= 0 && empty($currency->zero)) {
      $errors[$field['field_name']][$langcode][$delta][] = array(
        'error' => 'mcapi_invalid_zero',//this is not working as intended
        'message' =>  t('Zero value transactions not allowed in @currency', array('@currency' => $currency->human_name))
      );
    }

    //now check that the value is within the max limit, if specified
    //this isn't used at time of writing.
    if (!empty($element['#max'][$item['currcode']]) && $item['quantity'] > $element['#max'][$item['currcode']]) {
      $errors[$field['field_name']][$langcode][$delta][] = array(
        'error' => 'mcapi_over_max',//this is not working as intended
        'message' =>  t('The maximum value is !value', array('!value' => theme('worth_field', $item['quantity'])))
      );
    }
  }
}

/**
 * Implements hook_field_widget_error().
 */
function mcapi_field_widget_error($element, $error, $form, &$form_state) {
  switch ($error['error']) {
    case 'mcapi_invalid_currency':
      $error_element = $element['currcode'];
      break;
    case 'mcapi_invalid_division':
      $error_element = $element['division_quant'];
      break;
    default:
      $error_element = $element['main_quant'];
  }
  form_error($error_element, $error['message']);
}

/*
 * called by mcapi_theme, which implements hook_theme
 */
function mcapi_theme_fields() {
  return array(
    'worth_field' => array(
      'variables' => array(
        'currcode' => '',
        'quantity' => 0
      )
    ),
    'worth_field_multiple' => array(
      'render element' => 'items'
    ),
    'worth_widget' => array(
      'render element' => 'element'
    ),
    'worth_widget_multiple' => array(
      'render element' => 'element'
    ),
  );
}
/*
 * implements hook_preprocess_THEMEHOOK
 * if the form is being rebuilt, on an error, there's no other way to intervene
 * when the calculated quantity variable is dumped from $form_state['input] into $form
 */
function mcapi_preprocess_worth_widget(&$variables) {
  $element = &$variables['element'];
  if (isset($element['main_quant']['#value']) && !is_integer($element['main_quant']['#value'])) {
    list($element['main_quant']['#value'], $element['div_quant']['#value']) = currency_explode($element['main_quant']['#value']);
  }
}

/*
 * Default implementation of theme 'worth_field'
 * Converts the quantity into tokens and replaces them into the currency display format
 * $variables should have keys
 * 'quantity'
 * 'currcode'
 */
function theme_worth_field(&$variables) {
  $currency = currency_load($variables['currcode']);
  list($replacements['@integer'], $replacements['@subdivision'], $replacements['@minus']) = currency_explode($variables['quantity']);
  if ($currency->display['divisions'] == CURRENCY_DIVISION_MODE_CUSTOM) {
    $replacements['@subdivision'] = $currency->display['divisions_allowed'][intval($replacements['@subdivision'])];
  }
  return strtr($currency->display['format'], $replacements);
}
/*
 * default implementation of theme hook 'worth_field_multiple'
 * this should probably be removed in favour of the usual multiple field wrapper
 */
function theme_worth_field_multiple($variables) {
  $items = &$variables['items'];
  $output = array();
  foreach (element_children($items) as $name) {
    $output[] = theme('worth_field', $items[$name]);
  }
  return implode('<br />', $output);
}

function theme_worth_widget_multiple($variables) {
  foreach (element_children($variables['element']) as $key) {
    $children[] = drupal_render($variables['element']);
  }
  return implode('<br />', $children);
}

/*
 * Default implementation of theme 'worth_widget'
 * Render all currency widgets
 */
function theme_worth_widget($variables) {
  $element = &$variables['element'];
  //output starts with the currency field, whether hidden or showing
  $curr_chooser = drupal_render($element['currcode']);
  if ($element['currcode']['#type'] == 'hidden') {
    //if the currcode is set then render according to the currency format, with no currcode chooser
    $format = currency_load($element['currcode']['#value'])->display['format'];
  }
  else {
    //not knowing the currency, we don't know what divisions fields to show,
    //so just show the main quant field
    //the ajax will rebuild when the currency is chosen
    $format = '@integer';
  }
  $html = $curr_chooser.= strtr($format,
    array(
      '@minus' => '',//no negative valuesin a form widget
      '@integer' => drupal_render($element['main_quant']),
      '@subdivision' => !empty($element['division_quant']) ? drupal_render($element['division_quant']) : '',
    )
  );
  return $html;
}

function clear_def_cur() {
  cache_clear_all('currencies', 'cache');
  variable_del('currencies_default');
}

/*
 * implements hook_form_field_ui_field_edit_form_alter
 * remove the default 'worth' setting from the field settings form
 */
function mcapi_form_field_ui_field_edit_form_alter(&$form, $form_state) {
  if ($form['#instance']['field_name'] != 'worth') return;

  //change the cardinality field because there is only one worth value possible per currency
  $currcount = count(currencies_load());
  if ($currcount == 1) {
    $form['field']['cardinality'] = array(
      '#type' => 'hidden',
      '#value' => 1
    );
  }
  else {
    $form['field']['cardinality'] = array(
      '#title' => t('Payments per transaction'),
      '#description' => t('Maximum one per currency'),
      '#type' => 'radios',
      '#options' => array(
        1 => t('Choose one currency'),
        $currcount => t('One field per currency')
      ),
      '#default_value' => $form['field']['cardinality']['#default_value']
    );
  }
}
/*
 * implements hook_field_widget_settings_form
 * no special settings to add.
 */
function mcapi_field_widget_settings_form($field, $instance) {}

/*
 * element validation callback, based on element_validate_integer_positive
 */
function element_validate_integer_worth($element, &$form_state) {
  $value = $element['#value'] + 0;
  if (!empty($value) && (!is_integer($value) || $value <= 0)) {
    form_error($element, t('%name must be a positive integer.', array('%name' => $element['#title'])));
  }
}


//ajax callback for form widget
//returns widget formatted according to selected currency
function mcapi_ajax_worth($form, $form_state) {
  //set the ajax target wrapper to the id of the element above
  $trigger = $form_state['triggering_element'];
  $parent_count = count($trigger['#array_parents']);
  $delta = $trigger['#array_parents'][$parent_count-2];
  return $form['worth']['und'][$delta];
}

//utility function
//returns an array of integer, hundredths, and minus sign if applicable
function currency_explode($number) {
  return array_merge(explode('.', number_format(abs($number), 2, '.', '')), array($number < 0 ? '-' : ''));
}

/*
 * Imlements hook_form_form_ID_alter
 * prevents editing of the worth field attached to the transaction entity.
 */
function mcapi_form_field_ui_field_overview_form_alter(&$form, $form_state) {
  if ($form['#entity_type'] == 'transaction' && isset($form['fields']['worth'])) {
    $form['fields']['worth']['type']['#type'] = 'markup';
    $form['fields']['worth']['widget_type']['#type'] = 'markup';
  }
}

/*
 * integration with the entity module
 * these two functions present the components of the worth field as an entity property
 */
function entity_metadata_field_property_get_mcapi_currcode($data, $options, $name, $type, $info) {
  print_r($data);
  foreach ($data['und'] as $delta => $data) {
    $values[$delta] = $data['currcode'];
  }
  return $values;
}
function entity_metadata_field_property_get_mcapi_quantity($data, $options, $name, $type, $info) {
  foreach ($data['und'] as $delta => $data) {
    $values[$delta] = $data['quantity'];
  }
  return $values;
}
function entity_metadata_field_worth_get($entity, $options, $name, $type, $info) {
  return $entity->worth['und'];
}