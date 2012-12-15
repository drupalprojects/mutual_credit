<?php

/*
 * implements hook_tokens
 * $options is ver important because it tells us whether to render the form field or a text version of the value in it
 */
function mcapi_forms_tokens($type, $tokens, array $objects, array $options = array()) {
  $replacements = array();
  if ($type != 'mcapiform' || !isset($objects[$type])) return $replacements;
  $mcapiform = $objects[$type];

  $url_options = array('absolute' => TRUE);
  $sanitize = !empty($options['sanitize']);
  $language_code = NULL;

  if (isset($options['language'])) {
    $url_options['language'] = $options['language'];
    $language_code = $options['language']->language;
  }
  foreach ($tokens as $name => $original) {
    $replacements[$original] = drupal_render($mcapiform[$name]);
  }
  //now, since we couldn't pass the form by reference,
  //we need to render the rest of the form elements
  //and tack them onto one of the tokens.
  //before that we need to hide all the unused form elements

  mcapi_element_children_value($form, '#theme', 'hidden');
  foreach (element_children($mcapiform) as $name) {
    $item = drupal_render($mcapiform[$name]);
    $replacements[$original] .= $item;
  }
  return $replacements;
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
      'description' => t('Stage 2 only: '). ' '. t("When the transaction was created")
    ),
    'creator' => array(
      'name' => t('Creator'),
      'description' => ('Stage 2 only: '). ' '. t("The user who first recorded the transaction"),
    )
  );

  foreach (field_info_instances('transaction', 'transaction') as $fieldname => $instance) {
    $mcapiform[$fieldname] = array(
      'name' => $instance['label'],
      'description' => isset($instance['description']) ? $instance['description'] : ''
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
  if (is_array($element)) {
    $element[$name] = $value;
    if ($fieldnames = element_children($element)) {
      foreach($fieldnames as $fieldname) {
        mcapi_element_children_value($element[$fieldname], $name, $value);
      }
    }
  }
}
