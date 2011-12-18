<?php

/*
 * Implements ctools hook default_mcapiforms
 */
function mcapi_pending_mcapiforms_default() {

  $mcapiform = new stdClass;
  $mcapiform->disabled = FALSE; /* Edit this to true to make a default mcapi_form disabled initially */
  $mcapiform->api_version = 3;
  $mcapiform->name = 'add_my_signature';
  $mcapiform->status = 1;
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
    'currcode' => array(
      'shared' => array(
        'preset' => 1,
        'widget' => 'hidden',
        'required' => FALSE,
      ),
    ),
    'payer' => array(
      'shared' => array(
        'preset' => '',
        'disable' => 0,
        'required' => 0,
      ),
      'args' => 'user_chooser_roles:2',
      'multiple' => 0,
    ),
    'payee' => array(
      'shared' => array(
        'preset' => '',
        'disable' => 0,
        'required' => 0,
      ),
      'args' => 'user_chooser_roles:2',
      'multiple' => 0,
    ),
    'worth' => array(
      'shared' => array(
        'preset' => '',
        'disable' => 0,
        'required' => 0,
        'multiple' => 0
      ),
      'chars' => '',
    ),
    'description' => array(
      'shared' => array(
        'preset' => '',
        'disable' => 0,
        'required' => 0,
      ),
    ),
    'submission' => array(
      'state' => TRANSACTION_STATE_FINISHED,
      'button' => 'Yes',
      'redirect' => 'transaction/%transaction',
    ),
    'step-2' => array(
      'title' => 'Sign this transaction',
      'template' => 'Do you want to sign this transaction?
[worth]',
    ),
    'buttons' => array(
      'submit' => 'Save',
      'delete' => 'Delete',
    ),
    'templates' => array(
      'template-1' => 'Do you want to sign this transaction?
[worth]',
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
