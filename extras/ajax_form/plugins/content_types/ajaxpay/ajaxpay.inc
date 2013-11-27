<?php

/**
 * Plugin definition.
 */
$plugin = array(
  'title' => t('Ajax transaction form'),
  'description' => t('Create a (1st party transaction) in place'),
  'required context' => new ctools_context_required(t('Node'), 'node'),
  'category' => t('Form'),
);

/**
 * Edit form.
 * No options for this yet
 */
function mcapi_ajaxform_ajaxpay_content_type_edit_form($form, &$form_state) {
  return $form;
}
function mcapi_ajaxform_ajaxpay_content_type_edit_form_submit($form, &$form_state) {

}
/**
 * Render callback.
 */
function mcapi_ajaxform_ajaxpay_content_type_render($subtype, $conf, $args, $context) {
  $node = &$context->data;
/*
 * this might have been used in the panel title
  $balance = theme('worth_item', array(
    'currcode' => 'credunit',
    'quantity' => transaction_totals($account->uid, 'credunit')->balance,
  ));
*/
  //first we create the default transaction from the node this is with
  $props['payer'] = $node->uid;
  $props['payee'] = $GLOBALS['user']->uid;
  $props['type'] = '1stparty'; //new types dcan be declared in hook_mcapi_info_types
  $props['state'] = TRANSACTION_STATE_PENDING;//signatures module will alter state according to type, using hook_form_alter
  $props['currcode'] = 'credunit';//until we get better information
  $props['quantity'] = 1;//until we get better information
  $props['description'] == $node->title;
  //get the field api fields and put them on the transaction
  foreach (array_keys(field_info_instances('node', $node->type)) as $field_name) {
    $props[$field_name] = $node->{$field_name};
  }


  $transaction = entity_create('transaction', $props);
debug($transaction);
  return (object) array(
    'title' => '<span class="title">' . t('Transfer') . '</span>',// <span class="balance">' . t('!balance', array('!balance' => $balance)). '</span>',
    'content' => drupal_get_form('mcapi_ajaxform', $transaction)
  );
}
