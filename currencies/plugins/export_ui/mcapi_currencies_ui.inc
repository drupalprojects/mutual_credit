<?php
$plugin = array(
  // The name of the table as found in the schema in hook_install. This
  // must be an exportable type with the 'export' section defined.
  'schema' => 'mcapi_currencies',

  // The access permission to use. If not provided it will default to
  // 'administer site configuration'
  'access' => 'manage all transactions',

  // You can actually define large chunks of the menu system here. Nothing
  // is required here. If you leave out the values, the prefix will default
  // to admin/structure and the item will default to the plugin name.
  'menu' => array(
    'menu prefix' => 'admin/accounting',
    'menu item' => 'currencies',
    'menu title' => 'Currencies configuration',
    'menu description' => 'Create, migrate and edit currencies',
  ),

  // These are required to provide proper strings for referring to the
  // actual type of exportable. "proper" means it will appear at the
  // beginning of a sentence.
  'title singular' => t('currency'),
  'title singular proper' => t('Currencies'),
  'title plural' => t('currencies'),
  'title plural proper' => t('Currencies'),

  // The item being edited will be in $form_state['item'].
  //
  // The submit handler is only responsible for moving data from
  // $form_state['values'] to $form_state['item'].
  //
  // All callbacks will accept &$form and &$form_state as arguments.
  'form' => array(
    'settings' => 'mcapi_currencies_form',
    'validate' => 'mcapi_currencies_form_validate',
    'submit' => 'mcapi_currencies_form_submit',
  ),
  'strings' => array(
    'confirmation' => array(
      'revert' => array(
        'information' => t('This action will permanently remove any customizations made to this currency.'),
        'success' => t('The currency has been reverted.'),
      ),
      'delete' => array(
        'information' => t('This action will permanently remove the currency from your database.'),
        'success' => t('The currency has been deleted.'),
      ),
    ),
  ),
  'handler' => 'mcapi_currencies_ui',
);
