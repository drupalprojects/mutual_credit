<?php

/*
 * Implements ctools hook default_mcapi_webforms
 */
function mcapi_pending_default_mcapi_webforms() {

  $webform = new stdClass;
  $webform->disabled = FALSE; /* Edit this to true to make a default webform disabled initially */
  $webform->api_version = 3;
  $webform->name = 'add_my_signature';
  $webform->path = 'transaction/%transaction/sign';
  $webform->status = 1;
  $webform->data = array(
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

  $webforms[$webform->name] = $webform;
  return $webforms;
}


/*
 * Implements hook_default_mcapi_webforms_alter
 * changes all default webforms which create new transactions to create them pending instead
 */
function mcapi_pending_default_mcapi_webforms_alter(&$webforms) {
  foreach ($webforms as $path => $webform) {
    if ($webform->data['workflow']['incoming'][t('Unset')] == t('Unset')) {
      $webform->data['workflow']['outgoing'] = TRANSACTION_STATE_PENDING;
      foreach (array('payer', 'payee', '2ndperson') as $participant) {
        if (isset($webform->data[$participant])) {
          $webforms[$path]->data[$participant]['signature'] = TRUE;
        }
      }
    }
  }
}