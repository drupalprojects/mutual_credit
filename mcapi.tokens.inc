<?php

/*
 * implements hook_tokens
 */
function mcapi_tokens($type, $tokens, array $data = array(), array $options = array()) {
  $replacements = array();
  if ($type != 'transaction' || empty($data['transaction'])) return $replacements;

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
      case 'xid':
        $replacements[$original] = $transaction->xid;
        break;
      case 'payer':
        $account = user_load($transaction->payer);
        $replacements[$original] = $sanitize ? filter_xss($account->name) : format_user($account);
        break;
      case 'payee':
        $account = user_load($transaction->payee);
        $replacements[$original] = $sanitize ? filter_xss($account->name) : format_user($account);
        break;
      case 'partner':
        $uid = $data['user']->uid == $transaction->payee ? $transaction->payee :$transaction->payer;
        $account = user_load($uid);
        $replacements[$original] = $sanitize ? filter_xss($account->name) : format_user($account);
        break;
      case 'creator':
        $account = user_load($transaction->creator);
        $replacements[$original] = $sanitize ? filter_xss($account->name) : format_user($account);
        break;
      case 'modifier':
        $account = user_load($transaction->modifier);
        $replacements[$original] = $sanitize ? filter_xss($account->name) : format_user($account);
        break;
      case 'worth':
        //could this be done with a render array?
        $replacements[$original] = theme('worth_fields', $transaction->worth['und']);
        debug($replacements[$original]);
        break;
      case 'summary':
        $replacements[$original] = theme('transaction', array('transaction' => $transaction, 'view_mode' => 'summary'));
        break;
      case 'uri':
        $replacements[$original] = url("transaction/$transaction->xid", $url_options);
        break;
      case 'created':
        $replacements[$original] = format_date($transaction->created, 'medium', '', NULL, $language_code);
        break;
      case 'modified':
        $replacements[$original] = format_date($transaction->modified, 'medium', '', NULL, $language_code);
        break;
    }
  }
  return $replacements;
}

function mcapi_token_info() {
  $types['transaction'] = array(
    'name' => t('Transactions'),
    'description' => t('Tokens related to individual transactions.'),
    'needs-data' => 'transaction',
  );
  $transaction = array(
    'xid' => array(
      'name' => t('Transaction ID'),
      'description' => t("The unique ID of the transaction."),
    ),
    'payer' => array(
      'name' => t('Payer name'),
      'description' => t("The user whose account is credit"),
    ),
    'payee' => array(
      'name' => t('Payee name'),
      'description' => t("The user whose account is debited"),
    ),
    'partner' => array(
      'name' => t('Other user'),
      'description' => t("The user who isn't referred to here."),
    ),
    'worth' => array(
      'name' => t('Values'),
      'description' => t("The currency flows"),
    ),
    'description' => array(
      'name' => t('Description'),
      'description' => t("A short desciption of the transaction"),
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
    'modifier' => array(
      'name' => t('Modifier'),
      'description' => t("The user who last changed the transaction"),
    ),
    'modified' => array(
      'name' => t('Created'),
      'description' => t("When the transaction was last modified"),
    ),
  );
  return array(
    'types' => $types,
    'tokens' => array('transaction' => $transaction),
  );
}