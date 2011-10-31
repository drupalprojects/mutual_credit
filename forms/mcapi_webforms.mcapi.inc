<?php

/*
 * Implements ctools hook mcapi_default_forms
 */
function mcapi_webforms_default_mcapi_webforms() {
  $webform = new stdClass;
  $webform->disabled = FALSE; /* Edit this to true to make a default webform disabled initially */
  $webform->api_version = 3;
  $webform->name = '1stparty';
  $webform->path = 'transact/1stparty';
  $webform->status = 1;
  $webform->data = array(
    'info' => array(
      'name' => '1stparty',
    ),
    'aspect' => '1',
    'help' => 'Enter your trading partner and direction of the payment',
    'outputs' => array(
      'access' => 'user_access:transact',
      'menu_link' => 'navigation',
      'use_block' => 1,
    ),
    'workflow' => array(
      'incoming' => array(
        'Unset' => 'Unset',
        'Finished' => 0,
      ),
      'outgoing' => '0',
    ),
    'webform_fields' => array(
      'webform_fields__active_tab' => 'edit-worth',
    ),
    '2ndperson' => array(
      'common' => array(
        'preset' => '',
        'disable' => 0,
        'required' => 1,
      ),
      'args' => 'user_chooser_permissions:transact',
    ),
    'direction' => array(
      'common' => array(
        'preset' => '',
        'disable' => 0,
        'required' => 1,
      ),
      'incoming' => 'Request',
      'outgoing' => 'Payment',
      'widget' => 'select',
    ),
    'body' => array(
      'common' => array(
        'preset' => '',
        'disable' => 0,
        'required' => 1,
      ),
    ),
    'worth' => array(
      'common' => array(
        'preset' => array(
          'integer' => '0',
          'currcode' => 'drupal7',
          'division' => '0',
        ),
        'disable' => 0,
        'required' => 1,
      ),
    ),
    'submission' => array(
      'button' => 'Submit',
      'redirect' => 'transaction/#xid',
    ),
    'webform_templates' => array(
      'webform_templates__active_tab' => 'edit-step-2',
    ),
    'step-1' => array(
      'title' => '1st party form',
      'template' => 'Partner: [2ndperson]<br />
  Direction: [direction]<br />
  Amount: [worth]<br />
  [body]<br />',
    ),
    'step-2' => array(
      'title' => 'Are you sure?',
      'template' => '[worth]
  with  [2ndperson]<br />
  for [body]<br />',
    ),
    'buttons' => array(
      'submit' => 'Save',
      'delete' => 'Revert',
    ),
    'templates' => array(
      'template-1' => 'Partner: [2ndperson]<br />
  Direction: [direction]<br />
  Amount: [worth]<br />
  [body]<br />',
    ),
  );

  $webforms[$webform->name] = $webform;

  
  $webform = new stdClass;
  $webform->disabled = FALSE; /* Edit this to true to make a default webform disabled initially */
  $webform->api_version = 3;
  $webform->name = '3rdparty';
  $webform->path = 'transact/3rdparty';
  $webform->status = 1;
  $webform->data = array(
    'info' => array(
      'name' => '3rdparty',
    ),
    'aspect' => '3',
    'help' => 'Enter the transaction for any two members',
    'outputs' => array(
      'access' => 'user_access:manage all transactions',
      'menu_link' => 'navigation',
      'use_block' => 1,
    ),
    'workflow' => array(
      'incoming' => array(
        'Unset' => 'Unset',
        'Finished' => 0,
      ),
      'outgoing' => '0',
    ),
    'webform_fields' => array(
      'webform_fields__active_tab' => 'edit-worth',
    ),
    'payer' => array(
      'common' => array(
        'preset' => '',
        'disable' => 0,
        'required' => 1,
      ),
      'args' => 'user_chooser_permissions:transact',
      'multiple' => 0,
    ),
    'payee' => array(
      'common' => array(
        'preset' => '',
        'disable' => 0,
        'required' => 1,
      ),
      'args' => 'user_chooser_permissions:transact',
      'multiple' => 0,
    ),
    'body' => array(
      'common' => array(
        'preset' => '',
        'disable' => 0,
        'required' => 1,
      ),
    ),
    'worth' => array(
      'common' => array(
        'preset' => array(
          'integer' => '0',
          'currcode' => 'drupal7',
          'division' => '0',
        ),
        'disable' => 0,
        'required' => 1,
      ),
    ),
    'submission' => array(
      'button' => 'Submit',
      'redirect' => 'transaction/#xid',
    ),
    'webform_templates' => array(
      'webform_templates__active_tab' => 'edit-step-2',
    ),
    'step-1' => array(
      'title' => '3rd party form',
      'template' => 'Payer: [payer]<br />
  Payee: [payee]<br />
  [body]<br />
  Amount: [worth]<br />',
    ),
    'step-2' => array(
      'title' => 'are you sure?',
      'template' => 'Payer: [payer]<br />
  Payee: [payee]<br />
  [body]<br />
  Amount: [worth]<br />',
    ),
    'buttons' => array(
      'submit' => 'Save',
      'delete' => 'Revert',
    ),
    'templates' => array(
      'template-1' => 'Payer: [payer]<br />
  Payee: [payee]<br />
  [body]<br />
  Amount: [worth]<br />',
    ),
  );
  $webforms[$webform->name] = $webform;


  $webform = new stdClass;
  $webform->disabled = FALSE; /* Edit this to true to make a default webform disabled initially */
  $webform->api_version = 3;
  $webform->name = 'many2one';
  $webform->path = 'transact/many2one';
  $webform->status = 1;
  $webform->data = array(
    'info' => array(
      'name' => 'many2one',
    ),
    'aspect' => '3',
    'help' => '',
    'outputs' => array(
      'access' => 'user_access:manage all transactions',
      'menu_link' => 'navigation',
      'use_block' => 0,
    ),
    'workflow' => array(
      'incoming' => array(
        'Unset' => 'Unset',
        'Finished' => 0,
      ),
      'outgoing' => '0',
    ),
    'webform_fields' => array(
      'webform_fields__active_tab' => 'edit-worth',
    ),
    'payer' => array(
      'common' => array(
        'preset' => '',
        'disable' => 0,
        'required' => 1,
      ),
      'args' => 'user_chooser_roles:2',
      'multiple' => 1,
    ),
    'payee' => array(
      'common' => array(
        'preset' => '',
        'disable' => 0,
        'required' => 1,
      ),
      'args' => 'user_chooser_roles:2',
      'multiple' => 0,
    ),
    'body' => array(
      'common' => array(
        'preset' => '',
        'disable' => 0,
        'required' => 1,
      ),
    ),
    'worth' => array(
      'common' => array(
        'preset' => array(
          'integer' => '0',
          'currcode' => 'drupal7',
          'division' => '0',
        ),
        'disable' => 0,
        'required' => 1,
      ),
    ),
    'submission' => array(
      'button' => 'Submit',
      'redirect' => 'transactions',
    ),
    'webform_templates' => array(
      'webform_templates__active_tab' => 'edit-step-2',
    ),
    'step-1' => array(
      'title' => 'Many pay one',
      'template' => '[payer]<br />
  will pay<br />
  [payee]<br />
  [worth] each<br />
  for<br />
  [body]',
    ),
    'step-2' => array(
      'title' => 'Are you sure?',
      'template' => '[payer]<br />
  will pay<br />
  [payee]<br />
  [worth] each<br />
  for<br />
  [body]',
    ),
    'buttons' => array(
      'submit' => 'Save',
      'delete' => 'Revert',
    ),
    'templates' => array(
      'template-1' => '[payer]<br />
  will pay<br />
  [payee]<br />
  [worth] each<br />
  for<br />
  [body]',
    ),
  );

  $webforms[$webform->name] = $webform;

  $webform = new stdClass;
  $webform->disabled = FALSE; /* Edit this to true to make a default webform disabled initially */
  $webform->api_version = 3;
  $webform->name = 'one2many';
  $webform->path = 'transact/one2many';
  $webform->status = 1;
  $webform->data = array(
    'info' => array(
      'name' => 'one2many',
    ),
    'aspect' => '3',
    'help' => '',
    'outputs' => array(
      'access' => 'user_access:manage all transactions',
      'menu_link' => 'navigation',
      'use_block' => 0,
    ),
    'workflow' => array(
      'incoming' => array(
        'Unset' => 'Unset',
        'Finished' => 0,
      ),
      'outgoing' => '0',
    ),
    'webform_fields' => array(
      'webform_fields__active_tab' => 'edit-worth',
    ),
    'payer' => array(
      'common' => array(
        'preset' => '',
        'disable' => 0,
        'required' => 1,
      ),
      'args' => 'user_chooser_roles:2',
      'multiple' => 0,
    ),
    'payee' => array(
      'common' => array(
        'preset' => '',
        'disable' => 0,
        'required' => 1,
      ),
      'args' => 'user_chooser_roles:2',
      'multiple' => 1,
    ),
    'body' => array(
      'common' => array(
        'preset' => '',
        'disable' => 0,
        'required' => 1,
      ),
    ),
    'worth' => array(
      'common' => array(
        'preset' => array(
          'integer' => '0',
          'currcode' => 'drupal7',
          'division' => '0',
        ),
        'disable' => 0,
        'required' => 1,
      ),
    ),
    'submission' => array(
      'button' => 'Submit',
      'redirect' => 'transactions',
    ),
    'webform_templates' => array(
      'webform_templates__active_tab' => 'edit-step-2',
    ),
    'step-1' => array(
      'title' => 'One pays many',
      'template' => '[payer]<br />
  will pay<br />
  [payee]<br />
  [worth] each<br />
  for<br />
  [body]',
    ),
    'step-2' => array(
      'title' => 'Are you sure?',
      'template' => '[payer]<br />
  will pay<br />
  [payee]<br />
  [worth] each<br />
  for<br />
  [body]',
    ),
    'buttons' => array(
      'submit' => 'Save',
      'delete' => 'Revert',
    ),
    'templates' => array(
      'template-1' => '[payer]<br />
  will pay<br />
  [payee]<br />
  [worth] each<br />
  for<br />
  [body]',
    ),
  );
  $webforms[$webform->name] = $webform;

  $webform = new stdClass;
  $webform->disabled = FALSE; /* Edit this to true to make a default webform disabled initially */
  $webform->api_version = 3;
  $webform->name = 'correction';
  $webform->path = 'transaction/%transaction/correction';
  $webform->status = 1;
  $webform->data = array(
    'info' => array(
      'name' => 'correction',
    ),
    'aspect' => '3',
    'help' => 'To change the participants, please erase or counter this transaction and make a new one',
    'outputs' => array(
      'access' => 'user_access:transact',
      'menu_link' => '0',
      'use_block' => 0,
    ),
    'workflow' => array(
      'incoming' => array(
        'Finished' => 'Finished',
        'Unset' => 0,
      ),
      'outgoing' => '0',
    ),
    'webform_fields' => array(
      'webform_fields__active_tab' => 'edit-worth',
    ),
    'payer' => array(
      'common' => array(
        'preset' => '',
        'disable' => TRUE,
        'required' => 1,
      ),
      'args' => 'user_chooser_permissions:transact',
      'multiple' => 0,
    ),
    'payee' => array(
      'common' => array(
        'preset' => '',
        'disable' => TRUE,
        'required' => 1,
      ),
      'args' => 'user_chooser_permissions:transact',
      'multiple' => 0,
    ),
    'body' => array(
      'common' => array(
        'preset' => '',
        'disable' => 0,
        'required' => 1,
      ),
    ),
    'worth' => array(
      'common' => array(
        'preset' => array(
          'integer' => '0',
          'currcode' => 'drupal7',
          'division' => '0',
        ),
        'disable' => 0,
        'required' => 1,
      ),
    ),
    'submission' => array(
      'button' => 'Submit',
      'redirect' => 'transaction/#xid',
    ),
    'webform_templates' => array(
      'webform_templates__active_tab' => 'edit-step-1',
    ),
    'step-1' => array(
      'title' => 'Transaction corrected',
      'template' => '[payer] paid [payee]<br />
  Amount: [worth]<br />
  For: [body]<br />',
    ),
    'step-2' => array(
      'title' => '',
      'template' => '',
    ),
    'buttons' => array(
      'submit' => 'Save',
      'delete' => 'Revert',
    ),
    'templates' => array(
      'template-1' => '[payer] paid [payee]<br />
  Amount: [worth]<br />
  For: [body]<br />',
    ),
  );

  $webforms[$webform->name] = $webform;
  return $webforms;
}
