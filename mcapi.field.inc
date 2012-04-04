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
 */
function mcapi_field_formatter_view($entity_type, $entity, $field, $instance, $langcode, $items, $display) {
  $element = array('#theme' => 'worth_fields');
  foreach($items as $delta => $item) {
    $element[$delta] = array(
      '#theme' => 'worth_field',
      '#currcode' => $item['currcode'],
      '#quantity' => $item['quantity'],
    );
  }
  return $element;
}

/*
 * implements hook_field_is_empty
 * tests to see if a field is empty
 */
function mcapi_field_is_empty($item) {
  return !$item['quantity'];
}

/*
 * implements hook_field_widget_form
 * not passing by reference at all because it isn't necessary
 * and screws up generating the pseudo fields in the mcapi_forms module, see _mcapi_edit_form
 */
function mcapi_field_widget_form($form, &$form_state, $field, $instance, $langcode, $items, $delta, $element) {
  $element['#delta'] = $delta;
  $item = &$items[$delta];
  $element['quantity'] = array(
    '#title' => t('Units'), //this is only ever seen on the error message anyway
    '#type' => 'textfield',
    '#default_value' => empty($item['quantity']) ? 0 : ($item['quantity']),
    '#size' => 4,
    '#maxlength' => 6,
    '#theme_wrappers' => array(),
  );
  $currencies = empty($element['#currcode']) ?
    currencies_load($GLOBALS['user']) : //needs to be for this current user
    array($element['#currcode'] => currency_load($element['#currcode']));

  $currcode = !empty($element['#currcode']) ? $element['#currcode'] : key($currencies);
  switch (count($currencies)) {
    case 0: throw new Exception (t('No currencies available to user.'));
    case 1:
      $element['currcode'] = array(
        '#type' => 'hidden',
        '#default_value' => $currcode,
      );
      $element['#theme'] = 'worth_widget_currcode';
      break;
    default:
      $element['currcode'] = currency_picker_element($currcode, NULL, currency_picker_element_options($currencies));
      $element['currcode']['#ajax'] = array(
        'callback' => 'mcapi_ajax_worth',
        'wrapper' => 'edit-worth-und-'.$delta
      );
      $element['#theme'] = 'worth_widgets';
  }
  $element['#element_validate'] = array('worth_widget_validate');
  $element['#process'] = array('mcapi_field_widget_process');
  return $element;
}

/*
 * form process callback
 * just processes one 'worth' at a time
 * this is run during the ajax rebuild, whereas hook_field_widget_form isn't
 * so this is the stage that formats the field according to currcode, which may have changed
 * tricky bit is setting the field[#default_value]s which are sometimes raw field data and sometimes from the db, sometimes passed in
 */
function mcapi_field_widget_process($element, $form_state, $form) {
  //usually this would have been done in the form build, but ajax needs a chance to change it, and ajax has pulled the $form out of cache
  //ajax
  if (empty($form_state['input']['worth']['und'][$element['#delta']])) {
    $default = array(
      'quantity' => 0,
      'division' => 0,
      'currcode' => $element['currcode']['#default_value'] ? $element['currcode']['#default_value'] : key(currencies_load())
    );
  }
  else {//'input' is the raw input from widgets; works for ajax AND multistep forms
    $default = $form_state['input']['worth']['und'][$element['#delta']];
  }
  $currency = currency_load($default['currcode']);//this should always be available
  //
  //in some cases because of the different ways the worth value comes in - from the widget or from the db
  //the worth value is in a different format. We need the division field here if the currency requires it
  if (!empty($default['division'])) {
    $default['quantity'] += $default['division'] / 100;
  }

  //now $default['quantity'] is a float, whichever format it arrived in.
  //if we are using divisions, then alter the quantity widget to be an integer and put the divisions in a new widget
  if (empty($currency->divisions)) {
    $element['quantity']['#default_value'] = $default['quantity'];
  }
  else {
    $element['quantity']['#default_value'] = intval($default['quantity']);
    $div = 100*(round($default['quantity'] - intval($default['quantity']), 2));
    if ($currency->divisions_setting == '01') {
      $element['division'] =  array(
        '#title' => t('Fraction'),
        '#type' => 'textfield',
        '#size' => 2,
        '#maxlength' => 2,
        '#field_prefix' => '.',
        '#default_value' => $div,
        '#theme_wrappers' => array(),
      );
    }
    else{
      $element['division'] = array(
        '#title' => t('Fraction'),
        '#options' => $currency->divisions,
        '#type' => 'select',
        '#field_prefix' => ':',
        '#default_value' => $div,
        '#theme_wrappers' => array(),
      );
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
 * element validation callback
 * adds division to quantity ready for as a 'worth' value
 * $element[#value] should already be set
 */
function worth_widget_validate(&$element, &$form_state) {
  $currcode = &$element['currcode']['#value'];
  $currency = currency_load($currcode);
  $division = NULL;
  foreach (array('quantity', 'division') as $subfield) {
    if (isset($element[$subfield]['#value'])) {
      $$subfield = $element[$subfield]['#value'] + 0;
      if ($$subfield) {
        if (!is_numeric($element[$subfield]['#value'])) {
          form_error($element[$subfield], t("Only numbers allowed for '@fieldname'", array('@fieldname' => $element[$subfield]['#title'])));
        }
      }
      if ($$subfield < 0) {
        form_error($element, t("Negative values not allowed for '@fieldname'", array('@fieldname' => $element[$subfield]['#name'])));
      }
    }
  }
  $value = $element['quantity']['#value'];
  if ($division) $value += $division/100;
  if ($value == 0 && !$currency->zero) {
    form_error($element, t('Zero transactions not allowed in @currency', array('@name' => $currency->name)));
  }

  //this affects form_state only, which later is used to set $element['#value']
  form_set_value(
    $element,
    array('currcode' => $currcode, 'quantity' => $value),
    $form_state
  );
  if ($division) {
    //check that the divisions are valid
    
    if ($currency->divisions == '01') return;
    if (isset($currency->divisions[$division])) return;

    form_error($element, t('Only certain centiles are permitted for @name', array('@name' => $currency->name)));
  }
  //now check that the value is within the max limit, if specified
  //this isn't used at time of writing.
  if (!empty($element['#max'][$currcode])) {
    if ($value > $element['#max'][$currcode]) {
      $replacements = array(
        '!amount' => theme('worth_field', array('quantity' => $element['#max'][$currcode], 'currcode' => $currcode))
      );
      form_error($element, t('The maximum value is !amount', $replacements));
    }
  }
}

/*
 * implements hook_field_validate
 * check if the current user has permission to use the currency in this flow.
 * runs AFTER worth_widget_validate
 * each item is an array(currcode => varchar, quantity => quantity, division => integer)
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
 */
function theme_worth_field(&$variables) {
  if (!$variables['currcode']) return '';
  $currency = currency_load($variables['currcode']);
  $num = $variables['quantity'] + 0;
  $integer = abs(intval($num));
  if ($num != $integer) {
    $hundredths = round(fmod(abs($num), 1), 2) * 100;
    if (is_array($currency->divisions)) {
      $subdivision = $currency->divisions[$hundredths];
    }
    else $subdivision = $hundredths;
  }
  return strtr(
    $currency->format,
    array(
      '@minus' => $num < 0 ? '-' :'',
      '@integer' => $integer,
      '@subdivision' => isset($subdivision) ? $subdivision : '00'
    )
  );
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
  return implode(", ", $elements);
}

/*
 * Default implementation of theme 'worth_widget_currcode'
 * for when the currency code is fixed
 */
function theme_worth_widget_currcode($variables) {
  $element = &$variables['element'];
  $currency = currency_load($element['currcode']['#default_value']);
  $currcode = drupal_render($element['currcode']);
  $element['#children'] = $currcode . strtr(
   $currency->format, //there better had be a default_value
    array(
      '@minus' => '',
      '@integer' => drupal_render($element['quantity']),
      '@subdivision' => !empty($element['division']) ? drupal_render($element['division']) : '',
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
  $row = array('data' => array(drupal_render($variables['element']['currcode']), drupal_render($variables['element']['quantity'])));

  if (!empty($variables['element']['division'])) {
    $row['data'][] = drupal_render($variables['element']['division']);
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
  if ($form['#instance']['field_name'] == 'value') {
    unset ($form['instance']['default_value_widget']);
    $max = count(currencies_load());
    $form['field']['cardinality']['#title'] = t('Number of currencies per transaction.');
    $form['field']['cardinality']['#description'] = "Using more than one is possible, but more work is needed to make this user-friendly, since many ways are possible.";
    $form['field']['cardinality']['#options'] = array(
      1 => 1,
      $max => $max
    );
  }
}