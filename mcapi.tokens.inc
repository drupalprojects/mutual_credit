<?php

/*
 * implements hook_tokens
 */
function mcapi_tokens($type, $tokens, array $data = array(), array $options = array()) {
  $replacements = array();
  switch($type) {
    case 'transaction': return mcapi_tokens_transaction($tokens, array_shift($data['transactions']), $options);
    case 'transactions': return mcapi_tokens_transactions($tokens, $data['transactions'], $options);
    default: return $replacements;
  }
}

function mcapi_tokens_transaction($tokens, $transaction, $options) {
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
        $replacements[$original] = url("transaction/$transaction->xid", $url_options);
        break;
      case 'created':
        $replacements[$original] = format_date($transaction->created, 'medium', '', NULL, $language_code);
        break;
      case 'worth':
        //there may be different incoming formats depending on whether we are rending a form or an entity
        $element = mcapi_field_formatter_view(NULL, NULL, NULL, NULL, NULL, $transaction->worth['und'], 'sentence');
        $replacements[$original] = drupal_render($element);
        break;
      default: //hopefully a field API field
        //we don't want all the gumph around it, so just render the children
        $replacements[$original] = drupal_render_children(field_view_field('transaction', $transaction, $name, 'default'));
        break;
    }
  }
  return $replacements;
}

function mcapi_tokens_transactions($tokens, $transactions, $options) {
  //run the tokens on the first transaction
  $transaction = current($transactions);
  //add up all the transactions.
  $diff = _transactions_diff($transactions);
  //I thought about passing this info from mcapi_limits like this:
  // $transactions[#dif] = array($payer_uid => $dif, $payee_uid = -$dif)
  //however mcapi_limits might not be installed
  //AND instead of doing foreach on $transactions, we would have to hack with element_children
  //another idea would be to prepare this info before
  $replacements = array();
  foreach ($tokens as $name => $original) {
    $worth = array();
    switch ($name) {
      case 'payer_diff': $uid = $transaction->payer; break;
      case 'payee_diff': $uid = $transaction->payee; break;
      default: continue 2;
    }
    foreach ($diff[$uid] as $currcode => $quantities) {
      $worth[] = array('currcode' => $currcode, 'quantity' => array_sum($quantities));
    }
    $element = mcapi_field_formatter_view(NULL, NULL, NULL, NULL, NULL, $worth, 'sentence');
    $replacements[$original] = drupal_render($element);
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
    ),
  );

  foreach (field_info_instances('transaction', 'transaction') as $fieldname => $field) {
    $transaction_tokens[$fieldname] = array(
      'name' => $field['label'],
      'description' => $field['description']
    );
  }

  $transactions_tokens = array(
    'payer_diff' => array(
      'name' => t("Cost to payer"),
      'description' => t("Total change to payers balance")
    ),
    'payee_diff' => array(
      'name' => t("Benefit to payee"),
      'description' => t("Total change to payees balance"),
    )
  );
  
  return array(
    'types' => array(
      'transaction' => array(
        'name' => t('Transactions'),
        'description' => t('Tokens related to individual transactions.'),
        'needs-data' => 'transaction',
      ),
      'transactions' => array(
        'name' => t('Transaction cluster'),
        'description' => t('Tokens related to clusters of transactions.'),
        'needs-data' => 'transactions',
        )
    ),
    'tokens' => array(
      'transaction' => $transaction_tokens,
      'transactions' => $transactions_tokens
    ),
  );
}