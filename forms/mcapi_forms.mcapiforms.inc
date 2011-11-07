<?php

/*
 * Implements ctools hook mcapi_default_forms
 */
function mcapi_forms_default_mcapiforms() {
  $mcapi_form = new stdClass;
  $mcapi_form->disabled = FALSE; /* Edit this to true to make a default mcapi_form disabled initially */
  $mcapi_form->api_version = 3;
  $mcapi_form->name = '1stparty';
  $mcapi_form->path = 'transact/1stparty';
  $mcapi_form->status = 1;
  $mcapi_form->data = array(
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
    'mcapi_form_fields' => array(
      'mcapi_form_fields__active_tab' => 'edit-worth',
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
    'mcapi_form_templates' => array(
      'mcapi_form_templates__active_tab' => 'edit-step-2',
    ),
    'step-1' => array(
      'title' => '1st party form',
      'template' => 'Partner: [2ndperson]<br />
  Direction: [direction]<br />
  Amount: [worth]<br />',
    ),
    'step-2' => array(
      'title' => 'Are you sure?',
      'template' => '[worth]
  with  [2ndperson]<br /><br />',
    ),
    'buttons' => array(
      'submit' => 'Save',
      'delete' => 'Revert',
    ),
    'templates' => array(
      'template-1' => 'Partner: [2ndperson]<br />
  Direction: [direction]<br />
  Amount: [worth]<br />',
    ),
  );

  $mcapi_forms[$mcapi_form->name] = $mcapi_form;

  
  $mcapi_form = new stdClass;
  $mcapi_form->disabled = FALSE; /* Edit this to true to make a default form disabled initially */
  $mcapi_form->api_version = 3;
  $mcapi_form->name = '3rdparty';
  $mcapi_form->path = 'transact/3rdparty';
  $mcapi_form->status = 1;
  $mcapi_form->data = array(
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
    'mcapi_form_fields' => array(
      'mcapi_form_fields__active_tab' => 'edit-worth',
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
    'mcapi_form_templates' => array(
      'mcapi_form_templates__active_tab' => 'edit-step-2',
    ),
    'step-1' => array(
      'title' => '3rd party form',
      'template' => 'Payer: [payer]<br />
  Payee: [payee]<br />
  Amount: [worth]<br />',
    ),
    'step-2' => array(
      'title' => 'are you sure?',
      'template' => 'Payer: [payer]<br />
  Payee: [payee]<br />
  Amount: [worth]<br />',
    ),
    'buttons' => array(
      'submit' => 'Save',
      'delete' => 'Revert',
    ),
    'templates' => array(
      'template-1' => 'Payer: [payer]<br />
  Payee: [payee]<br />
  Amount: [worth]<br />',
    ),
  );
  $mcapi_forms[$mcapi_form->name] = $mcapi_form;


  $mcapi_form = new stdClass;
  $mcapi_form->disabled = FALSE; /* Edit this to true to make a default form disabled initially */
  $mcapi_form->api_version = 3;
  $mcapi_form->name = 'many2one';
  $mcapi_form->path = 'transact/many2one';
  $mcapi_form->status = 1;
  $mcapi_form->data = array(
    'info' => array(
      'name' => 'many2one',
    ),
    'aspect' => '3',
    'help' => t('Make many transactions with different payers'),
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
    'mcapi_form_fields' => array(
      'mcapi_form_fields__active_tab' => 'edit-worth',
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
    'mcapi_form_templates' => array(
      'mcapi_form_templates__active_tab' => 'edit-step-2',
    ),
    'step-1' => array(
      'title' => 'Many pay one',
      'template' => '[payer]<br />
  will pay<br />
  [payee]<br />
  [worth] each<br />',
    ),
    'step-2' => array(
      'title' => 'Are you sure?',
      'template' => '[payer]<br />
  will pay<br />
  [payee]<br />
  [worth] each<br />',
    ),
    'buttons' => array(
      'submit' => 'Save',
      'delete' => 'Revert',
    ),
    'templates' => array(
      'template-1' => '[payer]<br />
  will pay<br />
  [payee]<br />
  [worth] each',
    ),
  );

  $mcapi_forms[$mcapi_form->name] = $mcapi_form;

  $mcapi_form = new stdClass;
  $mcapi_form->disabled = FALSE; /* Edit this to true to make a default form disabled initially */
  $mcapi_form->api_version = 3;
  $mcapi_form->name = 'one2many';
  $mcapi_form->path = 'transact/one2many';
  $mcapi_form->status = 1;
  $mcapi_form->data = array(
    'info' => array(
      'name' => 'one2many',
    ),
    'aspect' => '3',
    'help' => t('Make many transactions with different payees'),
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
    'mcapi_form_fields' => array(
      'mcapi_form_fields__active_tab' => 'edit-worth',
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
    'mcapi_form_templates' => array(
      'mcapi_form_templates__active_tab' => 'edit-step-2',
    ),
    'step-1' => array(
      'title' => 'One pays many',
      'template' => '[payer]<br />
  will pay<br />
  [payee]<br />
  [worth] each<br />',
    ),
    'step-2' => array(
      'title' => 'Are you sure?',
      'template' => '[payer]<br />
  will pay<br />
  [payee]<br />
  [worth] each<br />',
    ),
    'buttons' => array(
      'submit' => 'Save',
      'delete' => 'Revert',
    ),
    'templates' => array(
      'template-1' => '[payer]<br />
  will pay<br />
  [payee]<br />
  [worth] each',
    ),
  );
  $mcapi_forms[$mcapi_form->name] = $mcapi_form;

  $mcapi_form = new stdClass;
  $mcapi_form->disabled = FALSE; /* Edit this to true to make a default form disabled initially */
  $mcapi_form->api_version = 3;
  $mcapi_form->name = 'correction';
  $mcapi_form->path = 'transaction/%transaction/correction';
  $mcapi_form->status = 1;
  $mcapi_form->data = array(
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
    'mcapi_form_fields' => array(
      'mcapi_form_fields__active_tab' => 'edit-worth',
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
    'mcapi_form_templates' => array(
      'mcapi_form_templates__active_tab' => 'edit-step-1',
    ),
    'step-1' => array(
      'title' => 'Transaction corrected',
      'template' => '[payer] paid [payee]<br />
  Amount: [worth]<br />',
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
  Amount: [worth]<br />',
    ),
  );

  $mcapi_forms[$mcapi_form->name] = $mcapi_form;
  return $mcapi_forms;
}
