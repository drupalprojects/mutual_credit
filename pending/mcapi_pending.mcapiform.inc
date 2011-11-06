<?php

/*
 * Implements ctools hook default_mcapi_forms
 */
function mcapi_pending_default_mcapi_forms() {

  $mcapi_form = new stdClass;
  $mcapi_form->disabled = FALSE; /* Edit this to true to make a default mcapi_form disabled initially */
  $mcapi_form->api_version = 3;
  $mcapi_form->name = 'add_my_signature';
  $mcapi_form->path = 'transaction/%transaction/sign';
  $mcapi_form->status = 1;
  $mcapi_form->data = array(
    'info' => array(
      'name' => 'add_my_signature',
    ),
    'aspect' => '3',
    'help' => 'to sign a pending transaction',
    'outputs' => array(
      'access' => 'mcapi_access_check_roles:2',
      'menu_link' => 'devel',
      'use_block' => 0,
    ),
    'workflow' => array(
      'incoming' => array(
        'Pending' => 'Pending',
        'Unset' => 0,
        'Finished' => 0,
      ),
      'outgoing' => '0',
    ),
    'currcode' => array(
      'common' => array(
        'preset' => 1,
        'widget' => 'hidden',
        'required' => FALSE,
      ),
    ),
    'payer' => array(
      'common' => array(
        'preset' => '',
        'disable' => 0,
        'required' => 0,
      ),
      'args' => 'user_chooser_roles:2',
      'multiple' => 0,
    ),
    'payee' => array(
      'common' => array(
        'preset' => '',
        'disable' => 0,
        'required' => 0,
      ),
      'args' => 'user_chooser_roles:2',
      'multiple' => 0,
    ),
    'worth' => array(
      'common' => array(
        'preset' => '',
        'disable' => 0,
        'required' => 0,
        'multiple' => 0
      ),
      'chars' => '',
    ),
    'description' => array(
      'common' => array(
        'preset' => '',
        'disable' => 0,
        'required' => 0,
      ),
    ),
    'submission' => array(
      'button' => 'Yes',
      'redirect' => 'transaction/#xid',
    ),
    'step-1' => array(
      'title' => 'Sign this transaction',
      'template' => 'Do you want to sign this transaction?
  [description]<br />
  [amount]',
    ),
    'step-2' => array(
      'title' => '',
      'template' => '',
    ),
    'buttons' => array(
      'submit' => 'Save',
      'delete' => 'Delete',
    ),
    'templates' => array(
      'template-1' => 'Do you want to sign this transaction?
  [description]<br />
  [amount]',
    ),
    'email_template' => array(
      'subject' => 'Exchange saved with [transaction:partner]',
      'body' => '[user:name],

Your transaction with [transaction:partner] has been signed

More details can be found at
[transaction:uri]

To change your notification settings, visit your profile at
[user:edit-url]

The team at drupal7',
    )
  );

  $mcapi_forms[$mcapi_form->name] = $mcapi_form;
  return $mcapi_forms;
}


/*
 * Implements hook_default_mcapi_forms_alter
 * changes all default mcapi_forms which create new transactions to create them pending instead
 */
function mcapi_pending_default_mcapi_forms_alter(&$mcapi_forms) {
  foreach ($mcapi_forms as $path => $mcapi_form) {
    if ($mcapi_form->data['workflow']['incoming'][t('Unset')] == t('Unset')) {
      $mcapi_form->data['workflow']['outgoing'] = TRANSACTION_STATE_PENDING;
      foreach (array('payer', 'payee', '2ndperson') as $participant) {
        if (isset($mcapi_form->data[$participant])) {
          $mcapi_forms[$path]->data[$participant]['signature'] = TRUE;
        }
      }
    }
  }
}