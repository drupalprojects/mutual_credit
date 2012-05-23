<?php

/*
 * Implements ctools hook default_mcapiforms
 */
function mcapi_pending_mcapiforms_default() {

  $mcapiform = new stdClass;
  $mcapiform->disabled = FALSE; /* Edit this to true to make a default mcapi_form disabled initially */
  $mcapiform->api_version = 3;
  $mcapiform->name = 'add_my_signature';
  $mcapiform->data = array(
    'info' => array(
      'name' => 'add_my_signature',
    ),
    'perspective' => '3',
    'help' => 'to sign a pending transaction',
    'architecture' => array(
      'path' => 'transaction/%transaction/sign',
      'access' => 'mcapi_access_check_roles:2',
      'menu_link' => 'devel',
      'use_block' => 0,
    ),
    'submission' => array(
      'state' => TRANSACTION_STATE_FINISHED,
      'button' => 'Yes',
      'redirect' => 'transaction/%transaction',
    ),
    'experience' => array(
      'title' => '',
      'template' => '',
      'sure_title' => 'Sign this transaction',
      'sure_format' => 'certificate',
    ),
    'buttons' => array(
      'submit' => 'Save',
      'delete' => 'Delete',
    )
  );
  $mcapiforms[$mcapiform->name] = $mcapiform;
  return $mcapiforms;
}


/*
 * Implements hook_default_mcapiforms_alter
 * changes all other default mcapi_forms to output as pending instead
 */
function mcapi_pending_mcapiforms_default_alter(&$mcapiforms) {
  foreach ($mcapiforms as $name => $mcapiform) {
    if ($name == 'add_my_signature') continue;
    if ($mcapiform->data['submission']['state'] == TRANSACTION_STATE_FINISHED) {
      $mcapiform->data['submission']['state'] = TRANSACTION_STATE_PENDING;
    }
  }
}
