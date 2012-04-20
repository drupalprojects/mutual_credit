<?php

/*
 * Imlements hook_field_info
 */
function mcapi_field_info() {
  return array(
    'worth_field' => array(
      'label' => t('Worth'),
      'description' => t("Floating point value to two decimal places"),
      'settings' => array('allowed_values' => array(), 'allowed_values_function' => ''),
      'default_widget' => 'worth_widgets',
      'default_formatter' => 'worth_widgets',
      'translatable' => FALSE
    )
  );
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
 * implements hook_field_widget_info
 */
function mcapi_field_widget_info() {
  return array(
    'worth_widgets' => array(
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
    'worth_fields' => array(
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
  $element = array('#theme' => 'worth_fields');
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
    '#element_validate' => array('element_validate_integer_worth')
  );
  $currencies = currencies_load();
  if($field['cardinality'] > 1) {//if there are many currency fields, the default for each is set
    $currencies = array_keys(currencies_load($GLOBALS['user']));
    $default_vals['currcode'] = $currencies[$delta];
  }
  elseif (count($currencies) == 1) {
    $default_vals['currcode'] = key(currencies_load($GLOBALS['user']));
  }
  worth_element_build($element, $default_vals);

  $element['#element_validate'] = array('worth_widget_validate');
  $element['#process'] = array('mcapi_field_widget_process');
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
function mcapi_field_widget_process($element, $form_state, $form) {
  //determine the values for each of the three subfields, main_quant, division_quant, currcode
  //these are handled in order of priority as the default values could come from different sources
  //deal with any submitted values from ajax, or the back button
  if (isset($form_state['transaction']->worth['und'][$element['#delta']])) {//debug($form_state['transaction']);
    $value = $form_state['transaction']->worth['und'][$element['#delta']];
    $default_vals['currcode'] = $value['currcode'];
    list($default_vals['main_quant'], $default_vals['division_quant']) = currency_explode($value['quantity']);
    //coz its ajax, we rebuild most of the element
    worth_element_build($element, $default_vals);
  }

  //or prefilled values from a previous step
  elseif (isset($form_state['input']['worth']['und'][$element['#delta']])) {
    //actually if we are coming via the back button
    $default_vals = $form_state['input']['worth']['und'][$element['#delta']];
  }
  //or from the passed $element
  else {
    //list($default_vals['main_quant'], $default_vals['division_quant']) = currency_explode($element['#quantity']);
    $default_vals['currcode'] = isset($element['#currcode']) ? $element['#currcode'] : '';
  }
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
 * And because sometimes we the filled form, with defaults before processing
 * this function is called from both the form build function and the process function
 */
function worth_element_build(&$element, $default_vals) {
  //if this widget knows about one currency, or this element has more than one $item, then hide the curr chooser
  if (empty($default_vals['currcode'])) {//produces a dropdown if the currency is not preset.
    //get the widget from the currencies module, providing a default and a range of currency options
    $element['currcode'] = currency_picker_element($element['#currcode']);
    $element['#theme'] ='worth_widgets';
    $element['#required'] = TRUE;
  }
  else {
    $element['currcode'] = array(
      '#type' => 'hidden',
      '#default_value' => $default_vals['currcode'],
      '#ajax' => array(
        'callback' => 'mcapi_ajax_worth',
        'wrapper' => 'edit-worth-und-0'
      )
    );
    $element['#theme'] = 'worth_widget_currcode';
    $currency = currency_load($default_vals['currcode']);

    //Only show the divisions element if we know the currency and it has divisions. We are already showing integers
    if ($currency->display['divisions'] == CURRENCY_DIVISION_MODE_CENTS_FIELD) {
      $element['division_quant'] =  array(
        '#title' => t('Cents'),
        '#type' => 'textfield',
        '#size' => 2,
        '#maxlength' => 2,
        '#field_prefix' => '.',
        '#default_value' => $default_vals['division_quant'],
        '#element_validate' => array('element_validate_integer_worth')
      );
    }
    elseif ($currency->display['divisions'] == CURRENCY_DIVISION_MODE_CUSTOM){
      $element['division_quant'] = array(
        '#title' => t('Fraction'),
        '#options' => $currency->display['divisions_allowed'], //which MUST be an array because of _currency_divisions_array
        '#type' => 'select',
        '#field_prefix' => ':',
        '#default_value' => $default_vals['division_quant'],
        '#element_validate' => array('element_validate_integer_worth')
      );
    }
    //If the display format requires we can re-join the main part and the division if they belong in one widget
    if ($currency->display['divisions'] == CURRENCY_DIVISION_MODE_CENTS_INLINE) {
      //$element['main_quant']['#default_value'] .= '.'.$default_vals['division_quant'];
      $element['main_quant']['#element_validate'] = array('element_validate_number'); //will do for now
    }
  }
}

/*
 * element validation callback
 * adds division to quantity ready for as a 'worth' value
 * $element[#value] should already be set
 */
function worth_widget_validate(&$element, &$form_state, $form) {
  if ($form['#form_id'] == 'field_ui_field_edit_form') return;
  $value['currcode'] = &$element['currcode']['#value'];
  $currency = currency_load($value['currcode']);
  $division = in_array($currency->display['divisions'], array(CURRENCY_DIVISION_MODE_CENTS_FIELD, CURRENCY_DIVISION_MODE_CUSTOM)) ?
    intval($element['division_quant']['#value']) :
    0;
  $value['quantity']  = &$element['main_quant']['#value'];
  if ($division) {
    //check that divisions match allowed values for the currency
    if (count($currency->display['divisions_allowed']) && !isset($currency->display['divisions_allowed'][$division])) {
      form_error($element, t('Only certain centiles are permitted for @name', array('@name' => $currency->name)));
    }
    //combine integer and division to get quantity
    $value['quantity'] += $division/100;
  }
  if ($value['quantity'] <= 0 && empty($currency->zero)) {
    form_error($element, t('Zero value transactions not allowed in @currency', array('@currency' => $currency->name)));
  }

  //now check that the value is within the max limit, if specified
  //this isn't used at time of writing.
  if (!empty($element['#max'][$value['currcode']])) {
    if ($value['quantity'] > $element['#max'][$value['currcode']]) {
      form_error($element, t('The maximum value is !value', array('!value' => theme('worth_field', $value['quantity']))));
    }
  }
  $element['#value'] = $value;
  //this affects form_state only, which later is used to set $element['#value']
  form_set_value(
    $element,
    $value,
    $form_state
  );
  if (form_get_errors()) return;
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
        'error' => t('This currency does not exist, or you do not have permission to use it'),
        'message' => t('This currency does not exist, or you do not have permission to use it'),
      );
    }
  }
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
    'worth_fields' => array(
      'render element' => 'element'
    ),
    'worth_widget_currcode' => array(
      'render element' => 'element'
    ),
    'worth_widgets' => array(
      'render element' => 'element'
    )
  );
  return $items;
}

/*
 * Default implementation of theme 'worth_field'
 * Converts the quantity into tokens and replaces them into the currency display format
 */
function theme_worth_field(&$variables) {
  $currency = currency_load($variables['currcode']);
  $replacements['@minus'] = $variables['quantity'] < 0 ? '-' :'';
  list($replacements['@integer'], $replacements['@subdivision']) = currency_explode($variables['quantity']);
  if ($currency->display['divisions'] == CURRENCY_DIVISION_MODE_CUSTOM) {
    $replacements['@subdivision'] = $currency->display['divisions_allowed'][intval($replacements['@subdivision'])];
  }
  return strtr($currency->display['format'], $replacements);
}


/*
 * Default implementation of theme 'worth_fields'
 * puts a comma between each of several fields
 */
function theme_worth_fields(&$variables) {
  foreach (element_children($variables['element']) as $delta) {
    $variables['element'][$delta]['#theme'] = 'worth_field';
    $elements[] = drupal_render($variables['element'][$delta]);
  }
  return implode("<br />", $elements);
}

/*
 * Default implementation of theme 'worth_widget_currcode'
 * for when the currency code is fixed
 */
function theme_worth_widget_currcode($variables) {
  $element = &$variables['element'];
  $currency = currency_load($element['currcode']['#value']);
  $output = drupal_render($element['currcode']);
  $element['#children'] = $output . strtr(
    $currency->display['format'], //there better had be a default_value
    array(
      '@minus' => '',//no negative valuesin a form widget
      '@integer' => drupal_render($element['main_quant']),
      '@subdivision' => !empty($element['division_quant']) ? drupal_render($element['division_quant']) : '',
    )
  );
  $element['#attributes']['width'] = 100;
  return theme('form_element', array('element' => $element));
}

/*
 * Default implementation of theme 'worth_widgets'
 * for when the currency code is undetermined
 * we use a table just to keep disparate form widgets lined up
 */
function theme_worth_widgets($variables) {
  $element = &$variables['element'];
  $row = array('data' => array(
    drupal_render($variables['element']['currcode']),
    drupal_render($variables['element']['main_quant']
  )));

  if (!empty($variables['element']['division_quant'])) {
    $row['data'][] = drupal_render($variables['element']['division_quant']);
  }
  return theme('table', array(
    'rows' => array($row),
    'attributes' => array(
      'id' => 'edit-worth-und-'. $variables['element']['#delta'],
      'style' => 'width:10px' //as short as possible
    ),
    'caption' => empty($element['#title']) ? '' : $element['#title'],
  ));
}
/*
 * get the names of the given currencies in an array keyed by currcode
 * not currently used
 */
function currency_select($currencies) {
  foreach ($currencies as $currcode => $currency) {
    $selection[$currcode] = $currency->name;
  }
  return $selection;
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
  unset($form['instance']['default_value_widget'], $form['instance']['label'], $form['instance']['description']);

  //change the cardinality field because there is only one worth value possible per currency
  $currcodes = count(currencies_load());
  if ($currcodes == 1) {
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
        $currcodes => t('One field per currency')
      ),
      '#default_value' => $form['field']['cardinality']['#default_value']
    );
  }

}
/*
 * element validation callback, based on element_validate_integer_positive
 */
function element_validate_integer_worth($element, &$form_state) {
  $value = $element['#value'] + 0;
  if (!empty($value) && (!is_numeric($value) || intval($value) != $value || $value <= 0)) {
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
function currency_explode($number) {
  return explode('.', number_format($number, 2, '.', ''));
}
