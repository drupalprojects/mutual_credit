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
      'default_widget' => 'worth_widget',
      'default_formatter' => 'worth_widget', // This doesn't *have* to be the same name as default_widget's value, this is only coincidence
      'translatable' => FALSE
    )
  );
}

/*
 * Imlements hook_form_form_ID_alter
 * prevents editing of the worth field attached to the transaction entity.
 */
function mcapi_form_field_ui_field_overview_form_alter(&$form, $form_state) {
  if ($form['#entity_type'] == 'transaction') {
    $form['fields']['worth']['type']['#type'] = 'markup';
    $form['fields']['worth']['widget_type']['#type'] = 'markup';
  }
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
    'worth_widget' => array(
      'label' => t('Worth'),
      'field types' => array('worth_field'),
    )
  );
}
/*
 * implements hook_field_formatter_view
 */
function mcapi_field_formatter_view($entity_type, $entity, $field, $instance, $langcode, $items, $display) {
  foreach($items as $delta => $item) {
    $element[$delta]['#theme'] = 'worth_field';
    $element[$delta]['#worth'] = $item;
  }
  return $element;
}
/*
 * implements hook_field_is_empty
 * WHAT DOES THIS DO?
 */
function mcapi_field_is_empty($item, $field) {
  $temp = array_keys($field['columns']);
  $key = array_shift($temp);
  return empty($item[$key]);
}

/*
 * implements hook_field_widget_form
 * processes the formAPI item
 * not worrying about addanother right now
 * not passing by reference at all because it isn't necessary
 * and screws up generating the pseudo fields in the webforms module, see _mcapi_edit_webform
 */
//$settings = $form_state['field'][$instance['field_name']][$langcode]['field']['settings'];
function mcapi_field_widget_form($form, &$form_state, $field, $instance, $langcode, $items, $delta, $element) {

  $element['integer'] = array(
    '#title' => t('Units'), //this is only ever seen on the error message anyway
    '#type' => 'textfield',
    '#size' => 4,
    '#maxlength' => 4,
    '#theme_wrappers' => array(),
  );

  $currencies = currencies_load($GLOBALS['user']); //needs to be for this current user
  //if we are not offering a choice of currencies we can format it nicely, otherwise we need to put a select widget
  if (count($currencies) == 1 || !empty($element['#currcode'])) {//the widget knows what currency to work in.
    $currcode = !empty($element['#currcode']) ? $element['#currcode'] : key($currencies);
    $element['currcode'] = array(
      '#type' => 'hidden',
      '#default_value' => $currcode,
    );
    $element['#theme'] = 'worth_widget_currcode';
  }
  else{//the widget offers a selection of currencies
    $element['currcode'] = array(
      '#type' => 'select',
      '#options' => currency_select($currencies),
      '#weight' => -1,
      '#ajax' => array(
        'callback' => 'mcapi_ajax_worth',
        'wrapper' => 'edit-worth-und-'.$delta
      ),
    );
    $element['#theme'] = 'worth_widget';
  }
  $element['#element_validate'] = array('validate_worth');
  $element['#process'] = array('mcapi_field_widget_process');
  return $element;
}

/*
 * form process callback
 * this is run during the ajax rebuild, whereas hook_field_widget_form isn't
 * just processes one widget at a time
 */
function mcapi_field_widget_process($element, $form_state, $form) {
  file_put_contents('debug.txt', print_r($form_state, 1));
  //this isn't working at all, see below
  $default = !empty($form_state['values'][$element['#field_name']]['und'][$element['#delta']]) ?
    $form_state['values'][$element['#field_name']]['und'][$element['#delta']] :
   (isset($element['#value']) ? $element['#value'] : array('quantity' => 0, 'currcode' => '', 'division' => '00'));

  if (arg(0) == 'system') {
    //'input' works for ajax
    $default = $form_state['input']['worth']['und'][$element['#delta']];
  }
  elseif(isset($form_state['build_info']['args'])) {
    //this is for the transaction edit form
    $default = $form_state['build_info']['args'][0]->worth['und'][$element['#delta']];
  }
  else {
    //debug($element); //TEMP MEASURE!
    //applies if there is only one currency
    $default['currcode'] = isset($element['currcode']['#default_value']) ? $element['currcode']['#default_value'] : '';
  }

  debug($default);

  $element['integer']['#default_value'] = intval($default['quantity']);
  $element['currcode']['#default_value'] = $default['currcode'];
  //handle the divisions if a currency is selected or chosen
  if (!empty($default['currcode'])) {

    $currency = currency_load($default['currcode']);
    //if any currency on the form has subdivisions then output this field
    if (!empty($currency->divisions)) {
      $element['division'] = array(
        '#title' => t('Fraction'),
        '#default_value' => str_pad(fmod($default['quantity'], 1) * 100, 2, '0', STR_PAD_LEFT),
        '#theme_wrappers' => array(),
      );
      if ($currency->divisions_setting == '00') {
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
  return $element;
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

/*
 * implements hook_field_validate
 * validates each value submitted
 * each item is an array(currcode => integer, integer => integer, division => integer)
 * Pays particular attention to the currcodes
 */
function mcapi_field_validate($entity_type, $entity, $field, $instance, $langcode, $items, &$errors) {
  $permitted_currencies = currencies_load($GLOBALS['user']);
  foreach ($items as $delta => $item) {
    if (!isset($permitted_currencies[$item['currcode']])) {
      $errors[$field['field_name']][$langcode][$delta][] = array(
        'error' => 'invalid_currency',
        'message' => t('This currency does not exist, or you do not have permission to use it'),
      );
    }
    if (!is_numeric($item['quantity'])) {
      $errors[$field['field_name']][$langcode][$delta][] = array(
        'error' => 'invalid_quantity',
        'message' => t('This is not a valid quantity'),
      );
    }
    if ($item['quantity'] < 0) { //TODO: and if the field is required
      $errors[$field['field_name']][$langcode][$delta][] = array(
        'error' => 'negative_quantity',
        'message' => t('Negative values not allowed'),
      );
    }
    //check that the divisions are valid
    $currency = currency_load($item['currcode']);
    if ($currency->divisions == '00') return;
    $fraction = fmod($item['quantity'], 1) * 100;
    if (is_array($currency->divisions)) {
      if (isset($currency->divisions[$fraction])) return;
    }
    $errors[$field['field_name']][$langcode][$delta][] = array(
      'error' => 'invalid_fraction',
      'message' => t('Only certain centiles are permitted for @name', array('@name' => $currency->name)),
    );
    //this is not the place to check for zero values, because it used on the field settings page admin/accounting/transaction/fields
  }
}


/*
 * element validation callback
 * converts the integer & division into one float value, ready for submission
 */
function validate_worth(&$element, &$form_state) {
  $division = NULL;
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
  $field = $form_state['field'][$element['#field_name']][$element['#language']]['field'];
  $field_name = $field['field_name'];
  if (isset($form_state['values'][$field_name][$element['#language']][$element['#delta']])) {
    $values = $form_state['values'][$field_name][$element['#language']][$element['#delta']];
  }

  $float = $element['integer']['#value'];
  if ($division) $float += $division/100;
  form_set_value($element, array('currcode' => $element['currcode']['#value'], 'quantity' => $float), $form_state);
}

/*
 * Default implementation of theme 'worth_field'
 */
function theme_worth_field(&$variables) {
  $num = $variables['worth']['quantity'] + 0;
  $integer = abs(intval($num));
  if ($num != $integer) {
    $divisions = currency_load($variables['worth']['currcode'])->divisions;
    $fraction = abs($variables['worth']['quantity']);
    if (is_array($divisions)) {
      $subdivision = $divisions[str_pad(fmod($fraction, 1) * 100, 2, '0')];
    }
    else $subdivision = $fraction;
  }
  return strtr(
    currency_load($variables['worth']['currcode'])->format,
    array(
      '@minus' => $num < 0 ? '-' :'',
      '@integer' => $integer,
      '@subdivision' => isset($subdivision) ? $subdivision : '00'
    )
  );
}


/*
 * Default implementation of theme 'worth_fields'
 */
function theme_worth_fields(&$variables) {
  $element = $variables['element'];
  $children = element_children($element);
  $element['add_more']['#weight'] = count($children);
  $output = '';
  foreach ($children as $key) {
    $output .= drupal_render($element[$key]);
  }
  return $output;
}

/*
 * Default implementation of theme 'worth_widget_currcode'
 * for when the currency code is fixed
 */
function theme_worth_widget_currcode($variables) {
  $element = &$variables['element'];
  $currency = currency_load($element['currcode']['#default_value']);
  return strtr(
   $currency->format, //there better had be a default_value
    array(
      '@minus' => '',
      '@integer' => drupal_render($element['integer']),
      '@subdivision' => !empty($element['division']) ? drupal_render($element['division']) : '',
    )
  );
}

/*
 * Default implementation of theme 'worth_widget_currcode'
 * for when the currency code is undetermined
 * we use a table just to keep disparate form widgets lined up
 */
function theme_worth_widget($variables) {
  $variables['element']['currcode']['#options'] = array(t('Choose currency...')) + $variables['element']['currcode']['#options'];
  $row = array('data' => array(drupal_render($variables['element']['currcode']), drupal_render($variables['element']['integer'])));

  if (!empty($variables['element']['division'])) {
    $row['data'][] = drupal_render($variables['element']['division']);
  }
  return theme('table', array('rows' => array($row), 'attributes' => array('id' => 'edit-worth-und-'. $variables['element']['#delta'])));
}

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
 * remove the default value from the field settings form
 */
function mcapi_form_field_ui_field_edit_form_alter(&$form, $form_state) {
  unset ($form['instance']['default_value_widget']);
  debug($form);
  $max = count(currencies_load());
  $form['field']['cardinality']['#title'] = t('Number of currencies per transaction.');
  $form['field']['cardinality']['#description'] = "Using more than one is possible, but more work is needed to make this user-friendly, since many ways are possible.";
  $form['field']['cardinality']['#options'] = array(
    1 => 1,
    $max => $max
  );
}