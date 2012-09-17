<?php

/*
 * implements hook_tokens
 */
function mcapi_tokens($type, $tokens, array $data = array(), array $options = array()) {
  if ($type != 'transaction') return;
  $transaction = &$data['transaction'];
  $url_options = array('absolute' => TRUE);
  $sanitize = !empty($options['sanitize']);
  $language_code = NULL;

  if (isset($options['language'])) {
    $url_options['language'] = $options['language'];
    $language_code = $options['language']->language;
  }
  foreach ($tokens as $name => $original) {
    switch ($name) {
      // Simple key values on the node.
      case 'serial':
        $replacements[$original] = $transaction->serial;
        break;
      case 'payer':
        $account = user_load($transaction->payer);
        $replacements[$original] = $sanitize ? filter_xss($account->name) : format_username($account);
        break;
      case 'payee':
        $account = user_load($transaction->payee);
        $replacements[$original] = $sanitize ? filter_xss($account->name) : format_username($account);
        break;
      case 'creator':
        $account = user_load($transaction->creator);
        $replacements[$original] = $sanitize ? filter_xss($account->name) : format_username($account);
        break;
      case 'summary':
        $replacements[$original] = theme('transaction', array('transaction' => $transaction, 'view_mode' => 'sentence'));
        break;
      case 'uri':
        $replacements[$original] = url("transaction/$transaction->serial", $url_options);
        break;
      case 'created':
        $replacements[$original] = format_date($transaction->created, 'medium', '', NULL, $language_code);
        break;
      case 'worth':
        //there may be different incoming formats depending on whether we are rending a form or an entity
        $element = mcapi_field_formatter_view(NULL, NULL, NULL, NULL, NULL, $transaction->worth['und'], 'sentence');
        $replacements[$original] = drupal_render($element);
        break;
      case 'payer_diff':
      case 'payee_diff':
        $worth = array();
        $diff = _transactions_diff(array($transaction));
        $uid = $name == 'payer_diff' ? $transaction->payer : $transaction->payer;
        foreach ($diff[$uid] as $currcode => $quantities) {
          $worth[] = array('currcode' => $currcode, 'quantity' => array_sum($quantities));
        }
        $element = mcapi_field_formatter_view(NULL, NULL, NULL, NULL, NULL, $worth, 'sentence');
        $replacements[$original] = drupal_render($element);
      case 'url':
        //the token module handles this, but it mustn't be treated as 'default', below
        break;
      default: //hopefully a field API field
        //we don't want all the gumph around it, so just render the children
        $render_transaction = field_view_field('transaction', $transaction, $name, 'default');
        $replacements[$original] = drupal_render_children($render_transaction);
        break;
    }
  }
  return $replacements;
}

function mcapi_token_info() {
  $transaction_tokens = array(
    'serial' => array(
      'name' => t('Unique serial number'),
      'description' => t("The unique serial number of the transaction."),
    ),
    'payer' => array(
      'name' => t('Payer name'),
      'description' => t("The user whose account is credit"),
    ),
    'payee' => array(
      'name' => t('Payee name'),
      'description' => t("The user whose account is debited"),
    ),
    'summary' => array(
      'name' => t('Summary'),
      'description' => t("A one line description of the transaction. (An entity view mode)"),
    ),
    'uri' => array(
      'name' => t('Link'),
      'description' => t("An absolute link to the transaction"),
    ),
    'created' => array(
      'name' => t('Created'),
      'description' => t("When the transaction was created"),
    ),
    'creator' => array(
      'name' => t('Creator'),
      'description' => t("The user who first recorded the transaction"),
    )
  );

  foreach (field_info_instances('transaction', 'transaction') as $fieldname => $field) {
    $transaction_tokens[$fieldname] = array(
      'name' => $field['label'],
      'description' => isset($field['description']) ? $field['description'] : ''
    );
  }

  return array(
    'types' => array(
      'transaction' => array(
        'name' => t('Transactions'),
        'description' => t('Tokens related to transactions.'),
        'needs-data' => 'transaction',
      ),
    ),
    'tokens' => array(
      'transaction' => $transaction_tokens,
    ),
  );
}