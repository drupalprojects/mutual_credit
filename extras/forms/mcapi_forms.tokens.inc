<?php

/*
 * implements hook_tokens
 * $options is ver important because it tells us whether to render the form field or a text version of the value in it
 */
function mcapi_forms_tokens($type, $tokens, array $form, array $options = array()) {
  $replacements = array();
  if ($type != 'mcapiform') return $replacements;

  $url_options = array('absolute' => TRUE);
  $sanitize = !empty($options['sanitize']);
  $language_code = NULL;

  if (isset($options['language'])) {
    $url_options['language'] = $options['language'];
    $language_code = $options['language']->language;
  }
  if ($form['#step'] == MCAPIFORM_STEP_ENTER) {
    foreach ($tokens as $name => $original) {
      $replacements[$original] = drupal_render($form[$name]);
    }
  }
  elseif ($form['#step'] == MCAPIFORM_STEP_CONFIRM) {
    foreach ($tokens as $name => $original) {
      $replacements[$original] = mcapi_element_markup_render($form[$name]);
    }
  }
  //now, since we couldn't pass the form by reference,
  //we need to render the rest of the form elements
  //and tack them onto one of the tokens.
  //before that we need to hide all the unused form elements

  mcapi_element_children_value($form, '#theme', 'hidden');
  foreach (element_children($form) as $name) {
    $item = drupal_render($form[$name]);
    $replacements[$original] .= $item;
  }
  return $replacements;
}


/*
 * replace tokens in stage 2 form with text, based on the form item token used in the template
 * this is tricky because we have a formAPI element, and need to pull a sensible text value from it.
 */
function mcapi_element_markup_render(&$element) {
  $element['#printed'] = TRUE;
  switch($element['#type']) {
    case 'user_chooser_roles':
    case 'user_chooser_permissions':
      $def = is_numeric($element['#value']) ? array($element['#value']) : $element['#value'];
      return mcapi_format_usernames(user_load_multiple($def));
    case 'radios':
    case 'checkboxes':
    case 'select':
      return is_array($element['#value']) ?
        implode(', ', $element['#value']) :
        $element['#options'][$element['#value']];
    //all the fieldAPI fields appear here as container, here we take the form fields and convert them to a render array
    case 'container':
      $lang = current(element_children($element)); //assumes value is in one language only
      $fieldname = $element[$lang]['#field_name'];
      $field = field_info_field($fieldname);
      $instance = field_info_instance('transaction', $fieldname, 'transaction');
      $function  = $field['module'] . '_field_formatter_view';
      $items = mcapi_extract_element_values($element[$lang]);
      $renderable[] = $function('transaction', NULL, NULL, $instance, $lang, $items, $instance['display']['default']);
      return drupal_render($renderable);
    case 'textfield':
    default:
      debug("Mcapi forms doesn't know how to render ".$element['#type']);
      return serialize($element['#value']);
  }
}

function mcapi_extract_element_values($element) {
  $items = array();
  foreach (element_children($element) as $key) {
    foreach ($element[$key]['#columns'] as $column) {
      $items[$key][$column] = isset($element[$key][$column]['#default_value']) ? $element[$key][$column]['#default_value'] : $element[$key][$column]['#value'];
    }
  }
  return $items;
}

function mcapi_forms_token_info() {
  $types['mcapiform'] = array(
    'name' => t('Mcapi forms'),
    'description' => t('Tokens to put in stage 1 or stage 2 of the transaction forms'),
    'needs-data' => 'mcapiform',
  );

  $mcapiform = array(
    'payer' => array(
      'name' => t('Payer name'),
      'description' => t("The user whose account is credit"),
    ),
    'payee' => array(
      'name' => t('Payee name'),
      'description' => t("The user whose account is debited"),
    ),
    'secondperson' => array(
      'name' => t('Second person'),
      'description' => t("The user who isn't referred to here."),
    ),
    'created' => array(
      'name' => t('Created'),
      'description' => t("When the transaction was created"),
    ),
    'creator' => array(
      'name' => t('Creator'),
      'description' => t("The user who first recorded the transaction"),
    ),
    'modified' => array(
      'name' => t('Created'),
      'description' => t("When the transaction was last modified"),
    ),
  );

  foreach (field_info_instances('transaction', 'transaction') as $fieldname => $field) {
    $mcapiform[$fieldname] = array(
      'name' => $field['label'],
      'description' => $field['description']
    );
  }
  return array(
    'types' => $types,
    'tokens' => array('mcapiform' => $mcapiform),
  );
}


/*
 * nesting function to set values wherever in the element
 */
function mcapi_element_children_value(&$element, $name, $value) {
  $children = element_children($element);
  if (count($children)) {
    foreach($children as $child) {
      mcapi_element_children_value($element[$child], $name, $value);
    }
  }
  else {
    $element[$name] = $value;
  }
}