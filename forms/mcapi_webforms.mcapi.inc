<?php

/*
 * Implements ctools hook mcapi_default_forms
 */
function mcapi_webforms_default_mcapi_webforms() {
  $webform = new stdClass;
  $webform->disabled = FALSE; /* Edit this to true to make a default webform disabled initially */
  $webform->api_version = 3;
  $webform->name = '3rdparty';
  $webform->path = 'transaction/3rdparty';
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
        'Unset' => 0,
        'Finished' => 0,
        'Template' => 0,
      ),
      'outgoing' => '0',
    ),
    'webform_fields' => array(
      'webform_fields__active_tab' => 'edit-cid',
    ),
    'cid' => array(
      'common' => array(
        'preset' => '1',
        'disable' => 0,
        'required' => 1,
      ),
      'options' => array(
        1 => 0,
      ),
      'widget' => 'hidden',
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
    'amount' => array(
      'common' => array(
        'preset' => '',
        'disable' => 0,
        'required' => 1,
      ),
      'chars' => '4',
    ),
    'description' => array(
      'common' => array(
        'preset' => '',
        'disable' => 0,
        'required' => 1,
      ),
    ),
    'submission' => array(
      'button' => 'Submit',
      'redirect' => 'transaction/#xid',
    ),
    'templates' => array(
      'title-1' => '3rd party form',
      'template-1' => 'Payer: [payer]<br />
Payee: [payee]<br />
Description: [description]<br />
Amount: [amount]<br />',
      'title-2' => 'are you sure?',
      'template-2' => 'Payer: [payer]<br />
Payee: [payee]<br />
Description: [description]<br />
Amount: [amount]<br />',
    ),
    'buttons' => array(
      'submit' => 'Save',
      'delete' => 'Revert',
    ),
  );
  $webforms[$webform->name] = $webform;

  
  $webform = new stdClass;
  $webform->disabled = FALSE; /* Edit this to true to make a default webform disabled initially */
  $webform->api_version = 3;
  $webform->name = '1stparty';
  $webform->path = 'transaction/1stparty';
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
        'Template' => 0,
      ),
      'outgoing' => '0',
    ),
    'webform_fields' => array(
      'webform_fields__active_tab' => 'edit-cid',
    ),
    'cid' => array(
      'common' => array(
        'preset' => '',
        'disable' => 0,
        'required' => 1,
      ),
      'options' => array(
        1 => 0,
      ),
      'widget' => 'hidden',
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
    'amount' => array(
      'common' => array(
        'preset' => '',
        'disable' => 0,
        'required' => 1,
      ),
      'chars' => '4',
    ),
    'description' => array(
      'common' => array(
        'preset' => '',
        'disable' => 0,
        'required' => 1,
      ),
    ),
    'submission' => array(
      'button' => 'Submit',
      'redirect' => 'transaction/#xid',
    ),
    'templates' => array(
      'title-1' => '1st party form',
      'template-1' => 'Partner: [2ndperson]<br />
Direction: [direction]<br />
Amount: [amount]<br />
Description: [description]<br />',
      'title-2' => 'Are you sure?',
      'template-2' => '[amount]
with  [2ndperson]<br />
for [description]<br />',
    ),
    'buttons' => array(
      'submit' => 'Save',
      'delete' => 'Revert',
    ),
  );
  $webforms[$webform->name] = $webform;

  $webform = new stdClass;
  $webform->disabled = FALSE; /* Edit this to true to make a default webform disabled initially */
  $webform->api_version = 3;
  $webform->name = 'multiple';
  $webform->path = 'transaction/multiple';
  $webform->status = 1;
  $webform->data = array(
    'info' => array(
      'name' => 'multiple',
    ),
    'aspect' => '3',
    'help' => '',
    'outputs' => array(
      'access' => 'user_access:manage all transactions',
      'menu_link' => 'management',
      'use_block' => 0,
    ),
    'workflow' => array(
      'incoming' => array(
        'Unset' => 'Unset',
        'Finished' => 0,
        'Template' => 0,
      ),
      'outgoing' => '0',
    ),
    'webform_fields' => array(
      'webform_fields__active_tab' => 'edit-amount',
    ),
    'cid' => array(
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
      'multiple' => 1,
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
    'amount' => array(
      'common' => array(
        'preset' => '',
        'disable' => 0,
        'required' => 1,
      ),
      'chars' => '4',
    ),
    'description' => array(
      'common' => array(
        'preset' => '',
        'disable' => 0,
        'required' => 1,
      ),
    ),
    'submission' => array(
      'button' => 'Submit',
      'redirect' => 'transactions',
    ),
    'templates' => array(
      'title-1' => 'Enter multiple payments',
      'template-1' => '[payer]<br />
will pay<br />
[payee]<br />
[amount] each<br />
for<br />
[description]',
      'title-2' => 'Are you sure?',
      'template-2' => '[payer]<br />
will pay<br />
[payee]<br />
[amount] each<br />
for<br />
[description]',
    ),
    'buttons' => array(
      'submit' => 'Save',
      'delete' => 'Delete',
    ),
  );


  $webforms[$webform->name] = $webform;
  return $webforms;
}
