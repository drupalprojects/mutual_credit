<?php

/*
 * Implements ctools hook mcapi_default_forms
 * N.B. Because ctools doesn't seem to run cache as well as might be expected, this is called any time a form is needed
 * Also: these are saved in the menu item arguments which may cause unexpected cacheing behaviour
 */
function mcapi_forms_mcapiforms_default() {
  $forms = array('1stparty', '3rdparty', 'one2many', 'many2one', 'correction');
  foreach ($forms as $form_id) {
    $function = 'mcapiform_default_'.$form_id;
    $mcapiforms[$form_id] = $function();
  }
  return $mcapiforms;
}

function mcapiform_default_1stparty() {
  $mcapiform = new stdClass;
  $mcapiform->disabled = FALSE; /* Edit this to true to make a default mcapi_form disabled initially */
  $mcapiform->api_version = 3;
  $mcapiform->name = '1stparty';
  $mcapiform->status = 1;
  $mcapiform->data = array(
    'info' => array(
      'name' => '1stparty',
    ),
    'perspective' => '1',
    'help' => 'Enter your trading partner and direction of the payment',
    'architecture' => array(
      'path' => 'transact/1stparty',
      'access' => 'user_access:transact',
      'menu_link' => 'navigation',
      'use_block' => 1,
    ),
    '2ndperson' => array(
      'shared' => array(
        'preset' => '',
        'disable' => 0,
        'required' => 1,
      ),
      'args' => 'user_chooser_permissions:transact',
    ),
    'direction' => array(
      'shared' => array(
        'preset' => 'outgoing',
        'disable' => 0,
        'required' => 1,
      ),
      'incoming' => 'Request',
      'outgoing' => 'Payment',
      'widget' => 'select',
    ),
    'worth' => array(
      'shared' => array(
        'preset' => array(
          'und' => array(
            0 => array(
              'currcode' => current(currencies_load())->info['currcode'],
              'quantity' => '1',
            ),
          ),
        ),
        'disable' => 0,
        'required' => 1,
      ),
    ),
    'submission' => array(
      'state' => TRANSACTION_STATE_FINISHED,
      'button' => 'Submit',
      'redirect' => 'transaction/%transaction',
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
  return $mcapiform;
}

function mcapiform_default_3rdparty() {
  $mcapiform = new stdClass;
  $mcapiform->disabled = FALSE; /* Edit this to true to make a default form disabled initially */
  $mcapiform->api_version = 3;
  $mcapiform->name = '3rdparty';
  $mcapiform->status = 1;
  $mcapiform->data = array(
    'info' => array(
      'name' => '3rdparty',
    ),
    'perspective' => '3',
    'help' => 'Enter the transaction for any two members',
    'architecture' => array(
      'path' => 'transact/3rdparty',
      'access' => 'user_access:manage all transactions',
      'menu_link' => 'navigation',
      'use_block' => 1,
    ),
    'mcapi_form_fields' => array(
      'mcapi_form_fields__active_tab' => 'edit-worth',
    ),
    'payer' => array(
      'shared' => array(
        'preset' => '',
        'disable' => 0,
        'required' => 1,
      ),
      'args' => 'user_chooser_permissions:transact',
      'multiple' => 0,
    ),
    'payee' => array(
      'shared' => array(
        'preset' => '',
        'disable' => 0,
        'required' => 1,
      ),
      'args' => 'user_chooser_permissions:transact',
      'multiple' => 0,
    ),
    'worth' => array(
      'shared' => array(
        'preset' => array(
          'und' => array(
            0 => array(
              'currcode' => current(currencies_load())->info['currcode'],
              'quantity' => '0',
            ),
          ),
        ),
        'disable' => 0,
        'required' => 1,
      ),
    ),
    'submission' => array(
      'state' => TRANSACTION_STATE_FINISHED,
      'button' => 'Submit',
      'redirect' => 'transaction/%transaction',
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
  return $mcapiform;
}

function mcapiform_default_many2one() {
  $mcapiform = new stdClass;
  $mcapiform->disabled = FALSE; /* Edit this to true to make a default form disabled initially */
  $mcapiform->api_version = 3;
  $mcapiform->name = 'many2one';
  $mcapiform->path = 'transact/many2one';
  $mcapiform->status = 1;
  $mcapiform->data = array(
    'info' => array(
      'name' => 'many2one',
    ),
    'perspective' => '3',
    'help' => t('Make many transactions with different payers'),
    'architecture' => array(
      'path' => 'transact/many2one',
      'access' => 'user_access:manage all transactions',
      'menu_link' => 'navigation',
      'use_block' => 0,
    ),
    'mcapi_form_fields' => array(
      'mcapi_form_fields__active_tab' => 'edit-worth',
    ),
    'payer' => array(
      'shared' => array(
        'preset' => '',
        'disable' => 0,
        'required' => 1,
      ),
      'args' => 'user_chooser_roles:2',
      'multiple' => 1,
    ),
    'payee' => array(
      'shared' => array(
        'preset' => '',
        'disable' => 0,
        'required' => 1,
      ),
      'args' => 'user_chooser_roles:2',
      'multiple' => 0,
    ),
    'worth' => array(
      'shared' => array(
        'preset' => array(
          'und' => array(
            0 => array(
              'currcode' => current(currencies_load())->info['currcode'],
              'quantity' => '0',
            ),
          ),
        ),
        'disable' => 0,
        'required' => 1,
      ),
    ),
    'submission' => array(
      'state' => TRANSACTION_STATE_FINISHED,
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
  return $mcapiform;
}

function mcapiform_default_one2many() {
  $mcapiform = new stdClass;
  $mcapiform->disabled = FALSE; /* Edit this to true to make a default form disabled initially */
  $mcapiform->api_version = 3;
  $mcapiform->name = 'one2many';
  $mcapiform->path = 'transact/one2many';
  $mcapiform->status = 1;
  $mcapiform->data = array(
    'info' => array(
      'name' => 'one2many',
    ),
    'perspective' => '3',
    'help' => t('Make many transactions with different payees'),
    'architecture' => array(
      'path' => 'transact/one2many',
      'access' => 'user_access:manage all transactions',
      'menu_link' => 'navigation',
      'use_block' => 0,
    ),
    'mcapi_form_fields' => array(
      'mcapi_form_fields__active_tab' => 'edit-worth',
    ),
    'payer' => array(
      'shared' => array(
        'preset' => '',
        'disable' => 0,
        'required' => 1,
      ),
      'args' => 'user_chooser_roles:2',
      'multiple' => 0,
    ),
    'payee' => array(
      'shared' => array(
        'preset' => '',
        'disable' => 0,
        'required' => 1,
      ),
      'args' => 'user_chooser_roles:2',
      'multiple' => 1,
    ),
    'worth' => array(
      'shared' => array(
        'preset' => array(
          'und' => array(
            0 => array(
              'currcode' => current(currencies_load())->info['currcode'],
              'quantity' => '0',
            ),
          ),
        ),
        'disable' => 0,
        'required' => 1,
      ),
    ),
    'submission' => array(
      'state' => TRANSACTION_STATE_FINISHED,
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
  return $mcapiform;
}

function mcapiform_default_correction() {
  $mcapiform = new stdClass;
  $mcapiform->disabled = FALSE; /* Edit this to true to make a default form disabled initially */
  $mcapiform->api_version = 3;
  $mcapiform->name = 'correction';
  $mcapiform->status = 1;
  $mcapiform->data = array(
    'info' => array(
      'name' => 'correction',
    ),
    'perspective' => '3',
    'help' => 'To change the participants, please erase or counter this transaction and make a new one',
    'architecture' => array(
      'path' => 'transaction/%transaction/correction',
      'access' => 'user_access:transact',
      'menu_link' => 'finished',
      'use_block' => 'finished',
    ),
    'mcapi_form_fields' => array(
      'mcapi_form_fields__active_tab' => 'edit-worth',
    ),
    'payer' => array(
      'shared' => array(
        'preset' => '',
        'disable' => TRUE,
        'required' => 1,
      ),
      'args' => 'user_chooser_permissions:transact',
      'multiple' => 0,
    ),
    'payee' => array(
      'shared' => array(
        'preset' => '',
        'disable' => TRUE,
        'required' => 1,
      ),
      'args' => 'user_chooser_permissions:transact',
      'multiple' => 0,
    ),
    'worth' => array(
      'shared' => array(
        'preset' => array(
          'und' => array(
            0 => array(
              'currcode' => current(currencies_load())->info['currcode'],
              'quantity' => '0',
            ),
          ),
        ),
        'disable' => 0,
        'required' => 1,
      ),
    ),
    'submission' => array(
      'state' => TRANSACTION_STATE_FINISHED,
      'button' => 'Submit',
      'redirect' => 'transaction/%transaction',
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
  return $mcapiform;
}
