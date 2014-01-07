<?php

use \Drupal\field\Field;

/*
 * implements hook_tokens for a transaction cluster
 * process one transaction at a time, containing dependents
 * also see entity_tokens and field_tokens
 */
function mcapi_tokens($type, $tokens, array $data = array(), array $options = array()) {
  if ($type != 'mcapi' || !array_key_exists('mcapi', $data) || !is_object($data['mcapi'])) return array();
  $replacements = $replaced = array();
  $transaction = &$data[$type];
  module_load_include('inc', 'mcapi');
  mcapi_transaction_process_vars($replaced, $transaction, array('#showlinks' => in_array('links', $tokens)));

  foreach ($tokens as $name => $original) {
    $replacements[$original] = $replaced[$name];
  }
  //handle any entity references - tokens within tokens
  $token_service = \Drupal::token();
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
      'created',
      $created_tokens,
      array('created' => $transaction->created->value),
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
        'type' => array(
          'name' => t('Type'),
          'description' => t("The type, or workflow channel"),
        ),
        'state' => array(
          'name' => t('State'),
          'description' => t("The state of the transaction"),
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
 * helper function to get the token names for helptext token service and twig
* get the entity properties from mcapi_token_info, then the fieldapi fields
* returns an array
* this function would be handy for any entity_type
*/
function mcapi_transaction_list_tokens($showlinks = TRUE) {
  //no need to boot the whole token service, but maybe should get this from cache somehow?
  $data = mcapi_token_info();
  $allfields = Field::FieldInfo()->getFieldMap();
  $tokens = array_merge(
    array_keys($data['tokens']['mcapi']),
    array_keys($allfields['mcapi_transaction'])
  );
  if (!$showlinks) unset($tokens[array_search('links', $tokens)]);
  return $tokens;
}