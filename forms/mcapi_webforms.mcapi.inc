<?php

/*
 * Implements ctools hook mcapi_default_forms
 */
function mcapi_webforms_default_mcapi_webforms() {
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
        t('Unset') => t('Unset'),
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
      'widget' => 'radios',
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
    'step-1' => array(
      'title' => '3rd party form',
      'template' => 'Payer: [payer]<br />
Payee: [payee]<br />
Description: [description]<br />
Amount: [amount]<br />',
    ),
    'step-2' => array(
      'title' => 'are you sure?',
      'template' => 'Payer: [payer]<br />
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
        t('Unset') => t('Unset'),
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
      'widget' => 'radios',
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
    'step-1' => array(
      'title' => '1st party form',
      'template' => 'Partner: [2ndperson]<br />
Direction: [direction]<br />
Amount: [amount]<br />
Description: [description]<br />',
    ),
    'step-2' => array(
      'title' => 'Are you sure?',
      'template' => '[amount]
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
      'menu_link' => 'management',
      'use_block' => 0,
    ),
    'workflow' => array(
      'incoming' => array(
        t('Unset') => t('Unset'),
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
    'step-1' => array(
      'title' => 'Enter multiple payments',
      'template' => '[payer]<br />
will pay<br />
[payee]<br />
[amount] each<br />
for<br />
[description]'
    ),
    'step-2' => array(
      'title' => 'Are you sure?',
      'template' => '[payer]<br />
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
      'menu_link' => 'management',
      'use_block' => 0,
    ),
    'workflow' => array(
      'incoming' => array(
        t('Unset') => t('Unset'),
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
    'step-1' => array(
      'title' => 'Enter multiple payments',
      'template' => '[payer]<br />
will pay<br />
[payee]<br />
[amount] each<br />
for<br />
[description]'
    ),
    'step-2' => array(
      'title' => 'Are you sure?',
      'template' => '[payer]<br />
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
        'Template' => 0,
      ),
      'outgoing' => '0',
    ),
    'webform_fields' => array(
      'webform_fields__active_tab' => 'edit-payer',
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
    'webform_templates' => array(
      'webform_templates__active_tab' => 'edit-step-1',
    ),
    'step-1' => array(
      'title' => 'Correct transaction record',
      'template' => '[payer] paid [payee]<br />
  Amount: [amount]<br />
  For: [description]<br />',
    ),
    'step-2' => array(
      'title-2' => '',
      'template-2' => '',
    ),
    'buttons' => array(
      'submit' => 'Save',
      'delete' => 'Delete',
    ),
    'email_template' => array(
      'subject' => 'Exchange recorded with [transaction:partner]',
      'body' => '[user:name],

A transaction involving you and [transaction:partner] has been corrected.
The corrected amount is [transaction:amount]
The corrected description is "[transaction:description]"

To change your notification settings, visit your profile at
[user:edit-url]

The team at [site:name]',
      'send' => 0,
    ),
    'step-1' => array(
      'title' => t('Transaction corrected'),
      'template' => '[payer] paid [payee]<br />
Amount: [amount]<br />
For: [description]<br />',
    ),
  );

  $webforms[$webform->name] = $webform;
  return $webforms;
}
