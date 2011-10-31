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
    'worth_widgets' => array(
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
  $element = array();
  foreach($items as $delta => $item) {
    $element[$delta] = array(
      '#theme' => 'worth_field',
      '#currcode' => $item['currcode'],
    );
    //just for rendering the 2nd stage of the transaction form, which is a form item rendered as text, we have to check
    if (isset($item['quantity'])) {//normal
      $element[$delta]['#quantity'] = $item['quantity'];
    }
    else {//widget as text
      $element[$delta]['#quantity']  = $item['integer'] + $item['division']/100;
    }

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
 * and screws up generating the pseudo fields in the webforms module, see _mcapi_edit_webform
 */
function mcapi_field_widget_form($form, &$form_state, $field, $instance, $langcode, $items, $delta, $element) {
  $element['#delta'] = $delta;
  $element['integer'] = array(
    '#title' => t('Units'), //this is only ever seen on the error message anyway
    '#type' => 'textfield',
    '#size' => 4,
    '#maxlength' => 4,
    '#theme_wrappers' => array(),
  );
  $currencies = empty($element['#currcode']) ?
    currencies_load($GLOBALS['user']) : //needs to be for this current user
    array($element['#currcode'] => currency_load($element['#currcode']));
  switch (count($currencies)) {
    case 0: throw new Exception (t('No currencies available to user.'));
    case 1:
      $currcode = !empty($element['#currcode']) ? $element['#currcode'] : key($currencies);
      $element['currcode'] = array(
        '#type' => 'hidden',
        '#default_value' => $currcode,
      );
      $element['#theme'] = 'worth_widget_currcode';
      break;
    default:
      $options = currency_select($currencies);
      $cids = array_keys($options);
      $element['currcode'] = array(
        '#type' => 'select',
        '#options' => $options,
        '#default_value' => $cids[$delta],//this seems to be overridden somewhere.
        '#weight' => -1,
        '#ajax' => array(
          'callback' => 'mcapi_ajax_worth',
          'wrapper' => 'edit-worth-und-'.$delta
        ),
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
 * tricky bit is setting the field[#default_value]s which are sometimes raw field data and sometimes from the db
 */
function mcapi_field_widget_process($element, $form_state, $form) {
  $default = array();
  if (empty($element['currcode']['#options'])) {
    //applies if there is only one currency
    if (isset($element['currcode']['#default_value'])) {
      $default['currcode'] = $element['currcode']['#default_value'];
    }
    else {
      //module_load_include('admin.inc', 'mcapi', 'currencies');
      $default['currcode'] = currency_default()->info['currcode'];
    }
  }
  //if (arg(0) == 'system') {
  if (!empty($form_state['input']['worth']['und'][$element['#delta']])) {//'input' works for ajax AND multistep
    //'input' is from the raw widgets
    $default = $form_state['input']['worth']['und'][$element['#delta']];
  }
  //submitted entity
  elseif(!empty($element['#field_name']) && !empty($form_state['values'][$element['#field_name']])) {
    $default = $form_state['values'][$element['#field_name']]['und'][$element['#delta']];
  }
  //entity for building or rebuilding form
  elseif(!empty($element['#field_name']) && !empty($form_state['build_info']['args'][0]->{$element['#field_name']})) {
    // if this is a entity form, assume arg 0 is the transaction and we know what the defaults are.
    $default = $form_state['build_info']['args'][0]->{$element['#field_name']}['und'][$element['#delta']];

  }
  //in despair, now retrieving from the special form_state object created at step 1 validation
  elseif(!empty($form_state['#transaction'])) {
    $default = $form_state['#transaction']->worth['und'][$element['#delta']];
  }
  else {
    $default += array('integer' => 0, 'division' => 0);
    if (!empty($element['currcode']['#options'])) {
      //and setting the default currency according to the delta value of this widget
      $cids = array_keys($element['currcode']['#options']);
      $default['currcode'] = $cids[$element['#delta']];
    }
  }
  //in some cases because of the different ways the worth value comes in - from the widget or from the db
  //the worth value is in a different format. We need the integer/division here, not the quantity float
  if (!empty($default['quantity'])) {
    $default['integer'] = intval($default['quantity']);
    $default['division'] = str_pad(fmod($default['quantity'], 1) * 100, 2, '0', STR_PAD_LEFT);
  }
  
  $element['integer']['#default_value'] = intval($default['integer']);
  if (!empty($default['currcode'])) {
    $element['currcode']['#default_value'] = $default['currcode'];
    $currency = currency_load($default['currcode']);

    //if any currency on the form has subdivisions then output this field
    if (!empty($currency->divisions)) {
      $element['division'] = array(
        '#title' => t('Fraction'),
        //'#default_value' => ,
        '#default_value' => $default['division'],
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
 * element validation callback
 * converts the integer & division into one float value, ready for as a 'worth' value
 * $element[#value] should already be set
 */
function worth_widget_validate(&$element, &$form_state) {
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

  $value = $element['integer']['#value'];
  if ($division) $value += $division/100;
  //this affects form_state only, which later is used to set $element['#value']
  form_set_value(
    $element,
    array('currcode' => $element['currcode']['#value'], 'quantity' => $value),
    $form_state
  );

  if (!$division) return;
  //check that the divisions are valid
  $currency = currency_load($element['currcode']['#value']);
  if ($currency->divisions == '00') return;
  if (isset($currency->divisions[$division])) return;

  $errors[$field['field_name']]['und'][$delta][] = array(
    'error' => 'invalid_fraction',
    'message' => t('Only certain centiles are permitted for @name', array('@name' => $currency->name)),
  );
}

/*
 * implements hook_field_validate
 * validates each value submitted
 * runs AFTER worth_widget_validate
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
  }
}

/*
 * Default implementation of theme 'worth_field'
 */
function theme_worth_field(&$variables) {
  if (!$variables['currcode']) return '';
  $num = $variables['quantity'] + 0;
  $integer = abs(intval($num));
  $currency = currency_load($variables['currcode']);
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
 */
function theme_worth_fields(&$variables) {
  $element = $variables['element'];
  $output = '';
  foreach (element_children($element) as $delta) {
    $subelement = array(
      '#theme' => 'worth_field',
      '#currcode' => $element[$delta]['currcode'],
      '#quantity' => $element[$delta]['quantity']
    );
    $elements[] = drupal_render($subelement);
  }
  return implode("<br />\n", $elements);
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
      '@integer' => drupal_render($element['integer']),
      '@subdivision' => !empty($element['division']) ? drupal_render($element['division']) : '',
    )
  );
  return theme('form_element', array('element' => $element));
}

/*
 * Default implementation of theme 'worth_widgets'
 * for when the currency code is undetermined
 * we use a table just to keep disparate form widgets lined up
 */
function theme_worth_widgets($variables) {
  $element = &$variables['element'];
  //$element['currcode']['#options'] = array(t('Choose currency...')) + $element['currcode']['#options'];
  $row = array('data' => array(drupal_render($variables['element']['currcode']), drupal_render($variables['element']['integer'])));

  if (!empty($variables['element']['division'])) {
    $row['data'][] = drupal_render($variables['element']['division']);
  }
  return theme('table', array(
    'rows' => array($row),
    'attributes' => array('id' => 'edit-worth-und-'. $variables['element']['#delta']),
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
