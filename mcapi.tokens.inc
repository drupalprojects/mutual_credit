<?php

/*
 * implements hook_tokens for a transaction cluster
 * process one transaction at a time, containing dependents
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
        $replacements[$original] = drupal_render(transaction_view($transactions, 'sentences'));
        break;
      case 'certificate':
        $replacements[$original] = drupal_render(transaction_view($transactions, 'certificate'));
        break;
      case 'created':
        $replacements[$original] = format_date($transaction->created, 'medium', '', NULL, $language_code);
        break;
      case 'worth':
        //there may be different incoming formats depending on whether we are rending a form or an entity
        $element = mcapi_field_formatter_view(NULL, NULL, NULL, NULL, NULL, $transaction->worth['und'], 'sentences');
        $replacements[$original] = drupal_render($element);
        break;
      case 'payer_diff':
      case 'payee_diff':
        $worth = $items = array();
        $diff = _transactions_diff($transaction);
        $uid = $name == 'payer_diff' ? $transaction->payer : $transaction->payer;
        foreach ($diff[$uid] as $currcode => $quantities) {
          $items[] = array('currcode' => $currcode, 'quantity' => array_sum($quantities));
        }
        $element = mcapi_field_formatter_view(NULL, NULL, NULL, NULL, NULL, $items, 'sentences');
        $replacements[$original] = drupal_render($element);
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
      'description' => t("The unique serial number of the transaction cluster."),
    ),
    'payer' => array(
      'name' => t('Payer name'),
      'description' => t("The user whose account is credited"),
    ),
    'payee' => array(
      'name' => t('Payee name'),
      'description' => t("The user whose account is debited"),
    ),
    'payer_diff' => array(
      'name' => t("Difference to payer in the main transaction"),
      'description' => t("Total change to payers balance from transaction cluster")
    ),
    'payee_diff' => array(
      'name' => t("Difference to payee in the main transaction"),
      'description' => t("Total change to payees balance from transaction cluster"),
    ),
    'summary' => array(
      'name' => t('Summary'),
      'description' => t("A one-per-line description of the transaction cluster"),
    ),
    'certificate' => array(
      'name' => t('Certificate'),
      'description' => t("An HTML laid out view of the transaction cluster"),
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
        'name' => t('Transaction'),
        'description' => t('Tokens related to a transaction cluster.'),
        'needs-data' => 'transaction',
      ),
    ),
    'tokens' => array(
      'transaction' => $transaction_tokens,
    ),
  );
}
/*
 * implements hook_token_info_alter
 * remove the 'original' set of tokens, which is added to all entities,
 * but is not necessary, because transactions are not edited
 */
function mcapi_token_info_alter(&$items) {
  unset($items['tokens']['transaction']['original']);
}