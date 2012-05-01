<?php

/*
 * Implements ctools hook mcapi_default_forms
 * N.B. Because ctools doesn't seem to run cache as well as might be expected, this is called any time a form is needed
 * Also: these are saved in the menu item arguments which may cause unexpected cacheing behaviour
 */
function mcapi_forms_mcapiforms_default() {
  $forms = array('1stparty', '3rdparty', 'correction');
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
    'secondperson' => array(
      'preset' => '',
      'args' => 'user_chooser_permissions:transact',
    ),
    'direction' => array(
      'preset' => 'outgoing',
      'incoming' => 'Request',
      'outgoing' => 'Payment',
      'widget' => 'select',
    ),
    'worth' => array(
      'preset' => array(
        'und' => array(
          0 => array(
            'currcode' => current(currencies_load())->info['currcode'],
            'quantity' => '1',
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
      'template' => 'Partner: [mcapiform:secondperson]<br />
  Direction: [mcapiform:direction]<br />
  Amount: [mcapiform:worth]<br />',
    ),
    'step-2' => array(
      'title' => 'Are you sure?',
      'template' => '[mcapiform:worth]
  with  [mcapiform:secondperson]<br /><br />',
    ),
    'buttons' => array(
      'submit' => 'Save',
      'delete' => 'Revert',
    ),
    'templates' => array(
      'template-1' => 'Partner: [mcapiform:secondperson]<br />
  Direction: [mcapiform:direction]<br />
  Amount: [mcapiform:worth]<br />',
    ),
  );
  return $mcapiform;
}

function mcapiform_default_3rdparty() {
  $mcapiform = new stdClass;
  $mcapiform->disabled = FALSE; /* Edit this to true to make a default form disabled initially */
  $mcapiform->api_version = 3;
  $mcapiform->name = '3rdparty';
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
      'preset' => '',
      'args' => 'user_chooser_permissions:transact',
      'multiple' => 0,
    ),
    'payee' => array(
      'preset' => '',
      'args' => 'user_chooser_permissions:transact',
      'multiple' => 0,
    ),
    'worth' => array(
      'preset' => array(
        'und' => array(
          0 => array(
            'currcode' => current(currencies_load())->info['currcode'],
            'quantity' => '0',
          ),
        ),
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
      'template' => 'Payer: [mcapiform:payer]<br />
  Payee: [mcapiform:payee]<br />
  Amount: [mcapiform:worth]<br />',
    ),
    'step-2' => array(
      'title' => 'are you sure?',
      'template' => 'Payer: [mcapiform:payer]<br />
  Payee: [mcapiform:payee]<br />
  Amount: [mcapiform:worth]<br />',
    ),
    'buttons' => array(
      'submit' => 'Save',
      'delete' => 'Revert',
    ),
    'templates' => array(
      'template-1' => 'Payer: [mcapiform:payer]<br />
  Payee: [mcapiform:payee]<br />
  Amount: [mcapiform:worth]<br />',
    ),
  );
  return $mcapiform;
}

function mcapiform_default_correction() {
  $mcapiform = new stdClass;
  $mcapiform->disabled = FALSE; /* Edit this to true to make a default form disabled initially */
  $mcapiform->api_version = 3;
  $mcapiform->name = 'correction';
  $mcapiform->data = array(
    'info' => array(
      'name' => 'correction',
    ),
    'perspective' => '3',
    'help' => 'To change the participants, please erase or counter this transaction and make a new one',
    'architecture' => array(
      'path' => 'transaction/%transaction/formedit',
      'access' => 'user_access:transact',
      'menu_link' => 'finished',
      'use_block' => 'finished',
    ),
    'mcapi_form_fields' => array(
      'mcapi_form_fields__active_tab' => 'edit-worth',
    ),
    'payer' => array(
      'preset' => '',
      'args' => 'user_chooser_permissions:transact',
      'multiple' => 0,
    ),
    'payee' => array(
      'preset' => '',
      'args' => 'user_chooser_permissions:transact',
      'multiple' => 0,
    ),
    'worth' => array(
      'preset' => array(
        'und' => array(
          0 => array(
            'currcode' => current(currencies_load())->info['currcode'],
            'quantity' => '0',
          )
        )
      )
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
      'title' => 'Altering transaction',
      'template' => '[mcapiform:payer] paid [mcapiform:payee]<br />
  Amount: [mcapiform:worth]<br />',
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
      'template-1' => '[mcapiform:payer] paid [mcapiform:payee]<br />
  Amount: [mcapiform:worth]<br />',
    ),
  );
  return $mcapiform;
}
