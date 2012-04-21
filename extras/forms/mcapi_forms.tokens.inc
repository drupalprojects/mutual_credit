<?php

/*
 * implements hook_tokens
 * $options is ver important because it tells us whether to render the form field or a text version of the value in it
 */
function mcapi_forms_tokens($type, $tokens, array $data = array(), array $options = array()) {
  $replacements = array();
  if ($type != 'mcapiform_values' || empty($data['mcapiform_values'])) return $replacements;

  $values = &$data['values'];
  $url_options = array('absolute' => TRUE);
  $sanitize = !empty($options['sanitize']);
  $language_code = NULL;

  if (isset($options['language'])) {
    $url_options['language'] = $options['language'];
    $language_code = $options['language']->language;
  }
debug($tokens);
debug($values);
  foreach ($tokens as $name => $original) {
    switch ($name) {
      // Simple key values on the node.
      case 'payer':
        $replacements[$original] = $values['payer'];
        break;
    }
  }
  return $replacements;
}


function mcapi_forms_token_info() {
  $types['mcapiform_values'] = array(
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
    'partner' => array(
      'name' => t('Other user'),
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