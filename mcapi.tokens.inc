<?php

use Drupal\Core\Language\Language;

/*
 * implements hook_tokens for a transaction cluster
 * process one transaction at a time, containing dependents
 * also see entity_tokens and field_tokens
 */
function mcapi_tokens($type, $tokens, array $data = array(), array $options = array()) {
  if ($type != 'mcapi' || !array_key_exists('mcapi', $data) || !is_object($data['mcapi'])) return array();

  $token_service = \Drupal::token();

  $url_options = array('absolute' => TRUE);
  if (isset($options['langcode'])) {
    $url_options['language'] = language_load($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = Language::LANGCODE_DEFAULT;
  }
  $sanitize = !empty($options['sanitize']);
  $replacements = array();
  $transaction = &$data[$type];

  foreach ($tokens as $name => $original) {
    switch ($name) {
      case 'serial':
        $replacements[$original] = $transaction->serial->value;
        break;
      case 'worths':
        $replacements[$original] = $transaction->worths->value;
        break;
      case 'payer':
      case 'payee':
      case 'creator':
        $account = user_load($transaction->{$name}->value);
        $replacements[$original] = $sanitize ? check_plain($account->label()) : $account->label();
        break;
      case 'created':
        $replacements[$original] = format_date($transaction->created, 'medium', '', NULL, $langcode);
        break;
      case 'links':
        $ops = $transaction->links($transaction);
        $replacements[$original] = drupal_render($ops);
        break;
      case 'url':
        $replacements[$original] = url($transaction->uri(), $url_options);
    }
  }

  if ($payer_tokens = $token_service->findWithPrefix($tokens, 'payer')) {
    $replacements += $token_service->generate(
      'user',
      $payer_tokens,
      array('user' => $transaction->payer->value),
      $options
    );
  }
  if ($payee_tokens = $token_service->findWithPrefix($tokens, 'payee')) {
    $replacements += $token_service->generate(
      'user',
      $payee_tokens,
      array('user' => $transaction->payee->value),
      $options
    );
  }
  if ($creator_tokens = $token_service->findWithPrefix($tokens, 'creator')) {
    $replacements += $token_service->generate(
      'user',
      $creator_tokens,
      array('user' => $transaction->creator->value),
      $options
    );
  }
  if ($created_tokens = $token_service->findWithPrefix($tokens, 'created')) {
    $replacements += $token_service->generate(
      'date',
      $created_tokens,
      array('date' => $transaction->created->value),
      $options
    );
  }
  return $replacements;
}

function mcapi_token_info() {
  $info = array(
    'types' => array(
      'mcapi' => array(
        'name' => t('Transactions'),
        'description' => t('Tokens related to a community accounting transactions.'),
        'needs-data' => 'mcapi_transaction',
      ),
    ),
    'tokens' => array(
      'mcapi' => array(
        'serial' => array(
          'name' => t('Unique serial number'),
          'description' => t("The unique serial number of the transaction cluster."),
        ),
        'worths' => array(
          'name' => t('Worths'),
          'description' => t("The quantites of the transactions"),
        ),
        'payer' => array(
          'name' => t('Payer name'),
          'description' => t("The user whose account is credited"),
        ),
        'payee' => array(
          'name' => t('Payee name'),
          'description' => t("The user whose account is debited"),
        ),
        'creator' => array(
          'name' => t('Creator'),
          'description' => t("The user who registered the transaction"),
        ),
        'created' => array(
          'name' => t('Date created'),
          'description' => t("The date the transaction was registered"),
        ),
        'links' => array(
          'name' => t('Links'),
          'description' => t("Links to transaction operations"),
        ),
        'url' => array(
          'name' => t('URL'),
          'description' => t("The url of the transaction"),
        )
      )
    )
  );
  return $info;
}
/*
 * implements hook_token_info_alter
 * remove the 'original' set of tokens, which is added to all entities,
 * but is not necessary, because transactions are not edited
 */
function mcapi_token_info_alter(&$items) {
  print_r($items['tokens']['mcapi']);
  unset($items['tokens']['mcapi']);
}
