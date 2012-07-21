<?php

currencies_load();

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
    'architecture' => array(
      'path' => 'transaction/%transaction/sign',
      'access' => 'mcapi_access_check_roles:2',
      'menu_link' => MENU_LOCAL_ACTION,
      'use_block' => 0,
    ),
    'submission' => array(
      'button' => t('Yes'),
      'redirect' => '',
      'feedback' => t('You have signed the transaction')
    ),
    'experience' => array(
      'title' => '',
      'template' => '',
      'sure_title' => t('Sign transaction'),
      'sure_format' => 'sentence',
    ),
    'buttons' => array(
      'submit' => t('Save'),
      'delete' => t('Delete'),
    )
  );
  $mcapiforms[$mcapiform->name] = $mcapiform;
  return $mcapiforms;
}